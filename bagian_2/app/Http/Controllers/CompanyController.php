<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreCompanyRequest;
use App\Http\Requests\UpdateCompanyRequest;
use App\Models\Company;
use App\Services\CompanyService;
use Illuminate\Http\JsonResponse;
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
    public function index()
    {
        $companies = $this->companyService->paginateList();

        return view('companies.index', compact('companies'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
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
     * Show the form for editing the specified resource.
     */
    public function edit(Company $company)
    {
        return view('companies.edit', compact('company'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateCompanyRequest $request, Company $company)
    {
        try {
            $this->companyService->update($company->id, $request->validated(), $request->file('logo'));
            return redirect()->route('companies.index')->with('success', 'Company berhasil diupdate!');
        } catch (\Throwable $e) {
            return redirect()->back()->withInput()->with('error', 'Gagal mengupdate company');
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Company $company)
    {
        $result = $this->companyService->delete($company->id);

        if ($result) {
            return redirect()->back()->with('success', 'Company Berhasil Dihapus');
        }

        return redirect()->back()->with('error', 'Terjadi kesalahan saat menghapus');
    }

    /**
     * Serve company logo file.
     */
    public function logo(Company $company)
    {
        abort_unless($company->logo && Storage::disk('local')->exists($company->logo), 404);

        return Storage::disk('local')->response($company->logo);
    }

    /**
     * AJAX endpoint untuk select2 dropdown company.
     * Format response sesuai dengan select2 ajax data spec:
     * https://select2.org/data-sources/ajax
     *
     * Mendukung:
     * - q     : keyword pencarian (search by name)
     * - page  : nomor halaman pagination
     * - id    : (opsional) fetch satu company by ID — dipakai untuk pre-fill label
     */
    public function select2(Request $request): JsonResponse
    {
        // Mode fetch single by ID (untuk preserve old input setelah validation failure)
        if ($request->filled('id')) {
            $company = $this->companyService->find($request->input('id'));

            return response()->json([
                'results' => $company ? [['id' => $company->id, 'name' => $company->name]] : [],
                'pagination' => ['more' => false],
            ]);
        }

        $search = $request->input('q');
        $page = (int) $request->input('page', 1);

        $paginator = $this->companyService->paginateForSelect2($search, $page);

        return response()->json([
            'results' => $paginator->items(),
            'pagination' => [
                'more' => $paginator->hasMorePages(),
            ],
        ]);
    }
}
