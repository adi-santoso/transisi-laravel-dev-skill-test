<?php

namespace App\Imports;

use App\Models\Company;
use App\Models\Employee;
use Maatwebsite\Excel\Concerns\Importable;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithBatchInserts;
use Maatwebsite\Excel\Concerns\WithChunkReading;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

/**
 * Import employees dari Excel dengan chunk per 10 records.
 *
 * Format Excel yang diterima (heading row di baris pertama):
 * | name        | email             | company_name |
 * | John Doe    | john@example.com  | PT ABC       |
 *
 * CATATAN PENTING:
 * Import class ini TIDAK punya WithValidation — validation di-handle SEPENUHNYA
 * di EmployeeService::importFromExcel() dengan two-pass approach:
 *   Pass 1: Validate semua row (via EmployeesValidator) — kalau ada error, batalkan.
 *   Pass 2: Insert via class ini (sudah dijamin valid).
 *
 * Strategi ini menjamin all-or-nothing: import sukses penuh atau tidak sama sekali.
 *
 * Strategi:
 * - WithChunkReading      : baca file per 10 baris (hemat memory di file besar)
 * - WithBatchInserts      : insert ke DB per 10 record sekaligus (1 query / chunk)
 * - WithHeadingRow        : kolom diakses by name, bukan index
 */
class EmployeesImport implements ToModel, WithHeadingRow, WithChunkReading, WithBatchInserts
{
    use Importable;

    /**
     * Cache pemetaan company_name → company_id agar tidak query berulang.
     * @var array<string, int>
     */
    protected array $companyCache = [];

    public function model(array $row)
    {
        // Tidak perlu defensive check — sudah di-validate di Pass 1
        return new Employee([
            'name' => $row['name'],
            'email' => $row['email'],
            'company_id' => $this->resolveCompanyId($row['company_name']),
        ]);
    }

    protected function resolveCompanyId(string $companyName): int
    {
        $key = strtolower(trim($companyName));

        if (isset($this->companyCache[$key])) {
            return $this->companyCache[$key];
        }

        return $this->companyCache[$key] = Company::query()
            ->where('name', trim($companyName))
            ->value('id');
    }

    public function chunkSize(): int
    {
        return 10;
    }

    public function batchSize(): int
    {
        return 10;
    }
}
