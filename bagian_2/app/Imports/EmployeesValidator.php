<?php

namespace App\Imports;

use App\Models\Company;
use App\Models\Employee;
use Illuminate\Support\Facades\Validator;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithChunkReading;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

/**
 * Pass 1: Validator-only pass.
 * Tugas class ini cuma collect error per row tanpa insert apapun.
 *
 * Validasi yang dilakukan:
 * - Field standard (required, format) via Laravel Validator
 * - company_name harus exist di DB (custom rule via after())
 * - email tidak boleh duplicate dengan email yang sudah ada di DB
 * - email tidak boleh duplicate antar baris dalam file yang sama
 */
class EmployeesValidator implements ToCollection, WithHeadingRow, WithChunkReading
{
    /** @var array<string> Daftar nama company valid (lowercase, trimmed). */
    protected array $validCompanyNames;

    /** @var array<string> Email yang sudah ada di DB (lowercase). */
    protected array $existingEmails;

    /** @var array<string, int> Email yang sudah seen dalam file ini, mapped ke row pertama yang punya. */
    protected array $seenEmailsInFile = [];

    /** @var array<int, array{row: int, errors: array<string, array<int, string>>, values: array}> */
    protected array $errors = [];

    /** Track baris ke berapa di file (offset, dihitung manual karena pakai chunk). */
    protected int $rowOffset = 0;

    public function __construct()
    {
        // Pre-load semua company name untuk validasi cepat
        $this->validCompanyNames = Company::query()
            ->pluck('name')
            ->map(fn ($n) => strtolower(trim($n)))
            ->toArray();

        // Pre-load semua existing email untuk duplicate check
        $this->existingEmails = Employee::query()
            ->pluck('email')
            ->map(fn ($e) => strtolower(trim($e)))
            ->toArray();
    }

    public function collection(\Illuminate\Support\Collection $rows): void
    {
        foreach ($rows as $row) {
            $this->rowOffset++;
            $rowNumber = $this->rowOffset + 1; // +1 karena heading di baris 1

            $data = $row->toArray();

            $validator = Validator::make($data, [
                'name' => ['required', 'string', 'max:255'],
                'email' => ['required', 'email', 'max:255'],
                'company_name' => ['required', 'string', 'max:255'],
            ], [
                'name.required' => 'Kolom name wajib diisi.',
                'email.required' => 'Kolom email wajib diisi.',
                'email.email' => 'Format email tidak valid.',
                'company_name.required' => 'Kolom company_name wajib diisi.',
            ]);

            $validator->after(function ($v) use ($data, $rowNumber) {
                // company_name harus exist di DB
                $companyName = $data['company_name'] ?? null;
                if ($companyName) {
                    $key = strtolower(trim((string) $companyName));
                    if (!in_array($key, $this->validCompanyNames, true)) {
                        $v->errors()->add('company_name', "Company '{$companyName}' tidak ditemukan di sistem.");
                    }
                }

                // email duplicate check — hanya validasi kalau format email valid
                $email = $data['email'] ?? null;
                if ($email && filter_var($email, FILTER_VALIDATE_EMAIL)) {
                    $emailKey = strtolower(trim($email));

                    // Check duplicate dengan email yang sudah ada di DB
                    if (in_array($emailKey, $this->existingEmails, true)) {
                        $v->errors()->add('email', "Email '{$email}' sudah terdaftar di sistem.");
                    }

                    // Check duplicate dalam file
                    if (isset($this->seenEmailsInFile[$emailKey])) {
                        $firstRow = $this->seenEmailsInFile[$emailKey];
                        $v->errors()->add('email', "Email '{$email}' duplikat dengan baris {$firstRow}.");
                    } else {
                        $this->seenEmailsInFile[$emailKey] = $rowNumber;
                    }
                }
            });

            if ($validator->fails()) {
                $this->errors[] = [
                    'row' => $rowNumber,
                    'errors' => $validator->errors()->toArray(),
                    'values' => $data,
                ];
            }
        }
    }

    public function chunkSize(): int
    {
        return 10;
    }

    public function getErrors(): array
    {
        return $this->errors;
    }

    public function hasErrors(): bool
    {
        return !empty($this->errors);
    }
}
