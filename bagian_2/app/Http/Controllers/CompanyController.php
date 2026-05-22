<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreCompanyRequest;
use App\Http\Requests\UpdateCompanyRequest;
use App\Models\Company;
use App\Services\CompanyService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class CompanyController extends Controller
{

    protected CompanyService $companyService;

    public function __construct(CompanyService $companyService)
    {
        $this->companyService = $companyService;
    }

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $companies = $this->companyService->paginateList();

        return view('companies.index', compact('companies'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(Request $request)
    {
        return view('companies.create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreCompanyRequest $request)
    {
        try {
            $this->companyService->store($request->validated(), $request->file('logo'));
            return redirect()->route('companies.index')->with('success', 'Company tersimpan!');
        } catch (\Throwable $e) {
            return redirect()->back()->withInput()->with('error', 'Gagal menyimpan company');
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        $company = $this->companyService->find($id);
        return view('companies.edit', compact('company'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateCompanyRequest $request, string $id)
    {
        try {
            $this->companyService->update($id, $request->validated(), $request->file('logo'));
            return redirect()->route('companies.index')->with('success', 'Company berhasil diupdate!');
        } catch (\Throwable $e) {
            return redirect()->back()->withInput()->with('error', 'Gagal mengupdate company');
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $result = $this->companyService->delete($id);

        if($result){
            return redirect()->back()->with('success', 'Company Berhasil Dihapus');
        } else {
            return redirect()->back()->with('error', 'Terjadi kesalahan saat menghapus');
        }
    }

    public function logo(Company $company)
    {
        abort_unless($company->logo && Storage::exists($company->logo), 404);

        return Storage::disk('local')->response($company->logo);
    }
}
