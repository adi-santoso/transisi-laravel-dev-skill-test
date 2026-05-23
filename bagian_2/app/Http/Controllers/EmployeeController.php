<?php

namespace App\Http\Controllers;

use App\Http\Requests\ImportEmployeesRequest;
use App\Http\Requests\StoreEmployeeRequest;
use App\Http\Requests\UpdateEmployeeRequest;
use App\Models\Employee;
use App\Services\EmployeeService;

class EmployeeController extends Controller
{
    protected EmployeeService $employeeService;

    public function __construct(EmployeeService $employeeService)
    {
        $this->employeeService = $employeeService;
    }

    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $employees = $this->employeeService->paginateList();

        return view('employees.index', compact('employees'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return view('employees.create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreEmployeeRequest $request)
    {
        try {
            $this->employeeService->store($request->validated());
            return redirect()->route('employees.index')->with('success', 'Employee tersimpan!');
        } catch (\Throwable $e) {
            return redirect()->back()->withInput()->with('error', 'Gagal menyimpan employee');
        }
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Employee $employee)
    {
        // Eager load company agar select2 bisa pre-fill dengan label
        $employee->load('company:id,name');
        return view('employees.edit', compact('employee'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateEmployeeRequest $request, Employee $employee)
    {
        try {
            $this->employeeService->update($employee->id, $request->validated());
            return redirect()->route('employees.index')->with('success', 'Employee berhasil diupdate!');
        } catch (\Throwable $e) {
            return redirect()->back()->withInput()->with('error', 'Gagal mengupdate employee');
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Employee $employee)
    {
        $result = $this->employeeService->delete($employee->id);

        if ($result) {
            return redirect()->back()->with('success', 'Employee Berhasil Dihapus');
        }

        return redirect()->back()->with('error', 'Terjadi kesalahan saat menghapus');
    }

    /**
     * Tampilkan form untuk import Excel.
     */
    public function importForm()
    {
        return view('employees.import');
    }

    /**
     * Process import file Excel.
     */
    public function import(ImportEmployeesRequest $request)
    {
        try {
            $result = $this->employeeService->importFromExcel($request->file('file'));

            if (!$result['success']) {
                $errorCount = count($result['errors']);
                return redirect()->route('employees.import.form')
                    ->with('error', "Import dibatalkan. Ditemukan {$errorCount} baris dengan error. Tidak ada data yang masuk ke database.")
                    ->with('importErrors', $result['errors']);
            }

            return redirect()->route('employees.index')
                ->with('success', "Import berhasil! {$result['imported_count']} employee ditambahkan.");
        } catch (\Throwable $e) {
            return redirect()->route('employees.import.form')
                ->with('error', 'Gagal import: ' . $e->getMessage());
        }
    }
}
