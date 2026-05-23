<?php

namespace App\Services;

use App\Imports\EmployeesImport;
use App\Imports\EmployeesValidator;
use App\Models\Company;
use App\Repositories\Contracts\EmployeeRepositoryInterface;
use Barryvdh\Snappy\PdfWrapper;
use Illuminate\Database\QueryException;
use Illuminate\Http\Response;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Maatwebsite\Excel\Facades\Excel;

class EmployeeService
{

    protected EmployeeRepositoryInterface $employeeRepository;

    public function __construct(EmployeeRepositoryInterface $employeeRepository)
    {
        $this->employeeRepository = $employeeRepository;
    }

    public function paginateList()
    {
        return $this->employeeRepository->paginateList();
    }

    public function store(array $data)
    {
        try {
            DB::beginTransaction();

            $employee = $this->employeeRepository->create($data);

            DB::commit();

            return $employee->fresh();
        } catch (QueryException $e) {
            DB::rollBack();

            Log::error($e->getTraceAsString());

            throw $e;
        }
    }

    public function find($id)
    {
        return $this->employeeRepository->find($id);
    }

    public function update(string $id, array $data)
    {
        try {
            $employee = $this->employeeRepository->find($id);
            if (!$employee) return false;

            DB::beginTransaction();

            $this->employeeRepository->update($id, $data);

            DB::commit();

            return $employee->fresh();
        } catch (QueryException $e) {
            DB::rollBack();
            Log::error($e->getTraceAsString());
            throw $e;
        }
    }

    public function delete($id)
    {
        $employee = $this->employeeRepository->find($id);
        if (!$employee) return false;

        $result = DB::transaction(function () use ($id) {
            return $this->employeeRepository->delete($id);
        });

        return $result;
    }

    /**
     * Generate PDF berisi list employee untuk satu company.
     * Logo company di-embed sebagai base64 agar tidak butuh request HTTP
     * dari wkhtmltopdf ke server (file di storage/app tidak public-accessible).
     */
    public function exportPdfByCompany(Company $company): Response
    {
        $employees = $this->employeeRepository->getByCompany($company->id);

        $logoBase64 = null;
        if ($company->logo && Storage::disk('local')->exists($company->logo)) {
            $content = Storage::disk('local')->get($company->logo);
            $mime = Storage::disk('local')->mimeType($company->logo);
            $logoBase64 = "data:{$mime};base64," . base64_encode($content);
        }

        /** @var PdfWrapper $pdf */
        $pdf = app('snappy.pdf.wrapper');
        $pdf->loadView('exports.employees', [
            'company' => $company,
            'employees' => $employees,
            'logoBase64' => $logoBase64,
            'generatedAt' => now(),
        ])->setPaper('a4');

        $filename = 'employees-' . str()->slug($company->name) . '-' . now()->format('YmdHis') . '.pdf';

        return $pdf->download($filename);
    }

    /**
     * Import employees dari file Excel dengan strategi all-or-nothing.
     *
     * Two-pass approach:
     *   Pass 1: EmployeesValidator scan semua row, collect SEMUA error.
     *           Kalau ada minimal 1 error → return tanpa insert apapun.
     *   Pass 2: EmployeesImport insert via chunk per 10 (sudah dijamin valid).
     *
     * Database tidak akan terpengaruh kalau ada satupun row yang gagal validasi.
     *
     * @return array{success: bool, errors: array, imported_count: int}
     */
    public function importFromExcel(UploadedFile $file): array
    {
        // Pass 1: Validate semua row tanpa insert apapun
        $validator = new EmployeesValidator();
        Excel::import($validator, $file);

        if ($validator->hasErrors()) {
            Log::warning('Excel import dibatalkan karena validasi gagal', [
                'failure_count' => count($validator->getErrors()),
            ]);

            return [
                'success' => false,
                'errors' => $validator->getErrors(),
                'imported_count' => 0,
            ];
        }

        // Pass 2: Insert (sudah dijamin valid). Wrap dalam transaction untuk safety
        // — jika ada error tak terduga di tengah jalan (constraint violation, dll),
        // semua chunk yang sudah di-insert akan ikut ter-rollback.
        try {
            DB::transaction(function () use ($file) {
                $import = new EmployeesImport();
                Excel::import($import, $file);
            });

            // Re-read file untuk hitung jumlah row yang di-import (heading row tidak dihitung)
            $rowCount = $this->countDataRows($file);

            Log::info('Excel import sukses', ['imported_count' => $rowCount]);

            return [
                'success' => true,
                'errors' => [],
                'imported_count' => $rowCount,
            ];
        } catch (\Throwable $e) {
            Log::error('Excel import gagal di tahap insert', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            throw $e;
        }
    }

    /**
     * Hitung jumlah row data (tidak termasuk heading) di file Excel.
     * Dipakai untuk reporting jumlah yang ke-import.
     */
    private function countDataRows(UploadedFile $file): int
    {
        $array = Excel::toArray(null, $file);
        $sheet = $array[0] ?? [];

        // -1 untuk skip heading row
        return max(0, count($sheet) - 1);
    }
}
