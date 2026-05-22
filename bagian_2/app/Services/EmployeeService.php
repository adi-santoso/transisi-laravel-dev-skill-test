<?php

namespace App\Services;

use App\Models\Company;
use App\Repositories\Contracts\EmployeeRepositoryInterface;
use Barryvdh\Snappy\PdfWrapper;
use Illuminate\Database\QueryException;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

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
}
