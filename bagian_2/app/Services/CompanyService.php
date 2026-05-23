<?php

namespace App\Services;

use App\Repositories\Contracts\CompanyRepositoryInterface;
use Illuminate\Http\UploadedFile;
use Illuminate\Pagination\LengthAwarePaginator;
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

    public function paginateList()
    {
        return $this->companyRepository->paginateList();
    }

    public function paginateForSelect2(?string $search, int $page, int $perPage = 10): LengthAwarePaginator
    {
        return $this->companyRepository->paginateForSelect2($search, $page, $perPage);
    }

    /**
     * Store company baru.
     *
     * Priority logo source:
     *  1. $logo (UploadedFile baru di request ini)
     *  2. $tmpLogoFilename (dari preserve setelah validation gagal sebelumnya)
     *  3. (tidak boleh — required di FormRequest)
     */
    public function store(array $data, ?UploadedFile $logo, ?string $tmpLogoFilename = null)
    {
        $storedLogoPath = null;

        try {
            DB::beginTransaction();

            if ($logo) {
                // Case A: user upload file baru → pakai file baru.
                // Hapus tmp lama (kalau ada) supaya tidak orphan.
                $storedLogoPath = $this->storeLogo($logo);
                $data['logo'] = $storedLogoPath;
                if ($tmpLogoFilename) {
                    $this->deleteTmpLogo($tmpLogoFilename);
                }
            } elseif ($tmpLogoFilename) {
                // Case B: tidak upload baru tapi ada tmp_logo dari submit sebelumnya.
                // Move tmp file ke folder company.
                $storedLogoPath = $this->moveTmpLogoToCompany($tmpLogoFilename);
                $data['logo'] = $storedLogoPath;
            }
            // (else: tidak akan terjadi — FormRequest sudah enforce required.)

            // Bersihkan key field non-DB sebelum insert.
            unset($data['tmp_logo']);

            $company = $this->companyRepository->create($data);

            DB::commit();

            return $company->fresh();
        } catch (\Throwable $e) {
            if (DB::transactionLevel() > 0) {
                DB::rollBack();
            }

            if ($storedLogoPath) {
                Storage::disk('local')->delete($storedLogoPath);
            }

            Log::error($e->getTraceAsString());
            throw $e;
        }
    }

    public function find($id)
    {
        return $this->companyRepository->find($id);
    }

    public function update(string $id, array $data, ?UploadedFile $logo, ?string $tmpLogoFilename = null)
    {
        $newLogoPath = null;

        try {
            $company = $this->companyRepository->find($id);
            if (!$company) return false;

            DB::beginTransaction();

            $oldLogoPath = null;

            if ($logo) {
                $oldLogoPath = $company->logo;
                $newLogoPath = $this->storeLogo($logo);
                $data['logo'] = $newLogoPath;
                if ($tmpLogoFilename) {
                    $this->deleteTmpLogo($tmpLogoFilename);
                }
            } elseif ($tmpLogoFilename) {
                $oldLogoPath = $company->logo;
                $newLogoPath = $this->moveTmpLogoToCompany($tmpLogoFilename);
                $data['logo'] = $newLogoPath;
            } else {
                // PENTING: jangan biarkan key 'logo' overwrite jadi null
                unset($data['logo']);
            }

            unset($data['tmp_logo']);

            $this->companyRepository->update($id, $data);

            DB::commit();

            // Hapus file lama setelah commit (kalau replace logo)
            if ($oldLogoPath) {
                $this->deleteLogo($oldLogoPath);
            }

            return $company->fresh();
        } catch (\Throwable $e) {
            if (DB::transactionLevel() > 0) {
                DB::rollBack();
            }

            if ($newLogoPath) {
                Storage::disk('local')->delete($newLogoPath);
            }

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
        return Storage::disk('local')->delete($path);
    }

    /**
     * Pindahkan file dari storage/app/tmp/{filename} ke storage/app/company/{filename}.
     * Return path relatif baru (untuk disimpan di DB).
     */
    private function moveTmpLogoToCompany(string $filename): string
    {
        $disk = Storage::disk('local');
        $from = 'tmp/' . $filename;
        $to = 'company/' . $filename;

        if (!$disk->exists($from)) {
            // Edge case: tmp file ke-cleanup duluan oleh scheduled job.
            // Throw exception agar transaction rollback dan controller tampilkan error.
            throw new \RuntimeException("Tmp logo file not found: {$from}");
        }

        $disk->move($from, $to);

        return $to;
    }

    /**
     * Hapus tmp logo file dengan validasi format filename strict.
     * Mencegah accidental delete file lain karena filename tampered.
     */
    private function deleteTmpLogo(string $filename): void
    {
        if (!preg_match(\App\Http\Requests\StoreCompanyRequest::TMP_LOGO_REGEX, $filename)) {
            return;
        }

        try {
            Storage::disk('local')->delete('tmp/' . $filename);
        } catch (\Throwable $e) {
            Log::warning("Failed to delete tmp logo: {$filename}", ['error' => $e->getMessage()]);
        }
    }
}
