<?php

namespace App\Services;

use App\Repositories\Contracts\CompanyRepositoryInterface;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class CompanyService
{

    protected CompanyRepositoryInterface $companyRepository;

    public function __construct(CompanyRepositoryInterface $companyRepository)
    {
        $this->companyRepository = $companyRepository;
    }

    public function paginateList(){
        return $this->companyRepository->paginateList();
    }

    public function store(array $data, ?UploadedFile $logo){
        try{
            DB::beginTransaction();


            if ($logo) {
                $data['logo'] = $this->storeLogo($logo);
            }

            $company = $this->companyRepository->create($data);

            DB::commit();

            return $company->fresh();
        } catch (QueryException $e){
            DB::rollBack();

            Log::error($e->getTraceAsString());

            throw $e;
        }
    }

    public function find($id)
    {
        return $this->companyRepository->find($id);
    }

    public function update(string $id, array $data, ?UploadedFile $logo)
    {
        try {
            $company = $this->companyRepository->find($id);
            if (!$company) return false;

            DB::beginTransaction();

            $oldLogoPath = null;

            if ($logo) {
                $oldLogoPath = $company->logo;
                $data['logo'] = $this->storeLogo($logo);
            } else {
                // PENTING: jangan biarkan key 'logo' overwrite jadi null
                unset($data['logo']);
            }

            $this->companyRepository->update($id, $data);

            DB::commit();

            // Hapus file lama setelah commit (kalau replace logo)
            if ($oldLogoPath) {
                $this->deleteLogo($oldLogoPath);
            }

            return $company->fresh();
        } catch (QueryException $e) {
            DB::rollBack();
            Log::error($e->getTraceAsString());
            throw $e;
        }
    }

    public function delete($id)
    {
        $company = $this->companyRepository->find($id);
        if (!$company) return false;

        $logoPath = $company->logo;

        $result = DB::transaction(function () use ($id) {
            return $this->companyRepository->delete($id);
        });

        if ($logoPath && $result) {
            try {
                Storage::delete($logoPath);
            } catch (\Throwable $e) {
                // Log saja, jangan throw — DB sudah committed
                Log::warning("Failed to delete logo file: {$logoPath}", ['error' => $e->getMessage()]);
            }
        }

        return $result;
    }

    private function storeLogo(UploadedFile $file): string
    {
        // simpan ke storage/app/company
        return $file->store('company', 'local');
    }

    private function deleteLogo($path): bool
    {
        // simpan ke storage/app/company
        return Storage::delete($path);
    }
}
