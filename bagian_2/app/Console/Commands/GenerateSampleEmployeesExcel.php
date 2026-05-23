<?php

namespace App\Console\Commands;

use App\Models\Company;
use Illuminate\Console\Command;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;

/**
 * Generate sample Excel file untuk testing import employees.
 *
 * Email di-generate dengan suffix unique (timestamp + random) supaya
 * sample yang di-generate bisa di-import berkali-kali tanpa kena
 * unique constraint email.
 *
 * Usage:
 *   php artisan sample:employees-excel                              # 100 row valid
 *   php artisan sample:employees-excel --rows=50                    # custom jumlah
 *   php artisan sample:employees-excel --filename=valid.xlsx
 *   php artisan sample:employees-excel --type=error                 # file dengan baris error
 */
class GenerateSampleEmployeesExcel extends Command
{
    protected $signature = 'sample:employees-excel
                            {--rows=100 : jumlah row yang di-generate}
                            {--filename= : nama file output (auto-generated kalau kosong)}
                            {--type=valid : valid|error — tipe sample yang di-generate}';

    protected $description = 'Generate sample Excel file untuk testing import employees';

    public function handle(): int
    {
        $rows = (int) $this->option('rows');
        $type = $this->option('type');
        $filename = $this->option('filename') ?: $this->defaultFilename($type);

        $companies = Company::pluck('name')->toArray();

        if (empty($companies)) {
            $this->error('Tidak ada company di database. Tambahkan minimal 1 company dulu sebelum generate sample.');
            return self::FAILURE;
        }

        // Suffix unique per file generation untuk hindari email collision
        // antar run yang berbeda (saat unique constraint aktif).
        $emailSuffix = now()->format('YmdHis') . '-' . substr(bin2hex(random_bytes(2)), 0, 4);

        $data = match ($type) {
            'valid' => $this->generateValidRows($rows, $companies, $emailSuffix),
            'error' => $this->generateErrorRows($rows, $companies, $emailSuffix),
            default => null,
        };

        if ($data === null) {
            $this->error("Tipe '{$type}' tidak dikenali. Gunakan: valid, error");
            return self::FAILURE;
        }

        $this->info("Generating " . count($data) . " rows (type: {$type}, suffix: {$emailSuffix})...");

        $export = new class($data) implements FromArray, WithHeadings {
            use Exportable;

            public function __construct(private array $rows) {}

            public function array(): array
            {
                return $this->rows;
            }

            public function headings(): array
            {
                return ['name', 'email', 'company_name'];
            }
        };

        // Save langsung ke project root agar mudah diambil untuk submission tes
        $absolutePath = base_path($filename);
        file_put_contents($absolutePath, $export->raw(\Maatwebsite\Excel\Excel::XLSX));

        $this->info("File berhasil di-generate: {$absolutePath}");
        $this->line('');

        if ($type === 'error') {
            $this->warn('File ini berisi baris error untuk demo all-or-nothing.');
            $this->line('Saat di-import, seluruh import akan dibatalkan dengan error report.');
        }

        return self::SUCCESS;
    }

    private function defaultFilename(string $type): string
    {
        return match ($type) {
            'valid' => 'sample-employees.xlsx',
            'error' => 'sample-employees-error.xlsx',
            default => "sample-{$type}.xlsx",
        };
    }

    /**
     * Generate baris yang semuanya valid.
     */
    private function generateValidRows(int $rows, array $companies, string $suffix): array
    {
        $data = [];
        for ($i = 1; $i <= $rows; $i++) {
            $data[] = [
                'name' => $this->randomName($i),
                'email' => "employee{$i}-{$suffix}@example.com",
                'company_name' => $companies[array_rand($companies)],
            ];
        }
        return $data;
    }

    /**
     * Generate baris dengan campuran valid dan error untuk demo all-or-nothing.
     * Termasuk row dengan duplicate email (intra-file).
     */
    private function generateErrorRows(int $totalRows, array $companies, string $suffix): array
    {
        $data = [];

        // 1. Beberapa row valid di awal
        for ($i = 1; $i <= 5; $i++) {
            $data[] = [
                'name' => $this->randomName($i),
                'email' => "valid{$i}-{$suffix}@example.com",
                'company_name' => $companies[array_rand($companies)],
            ];
        }

        // 2. Row dengan name kosong
        $data[] = [
            'name' => '',
            'email' => "noname-{$suffix}@example.com",
            'company_name' => $companies[0],
        ];

        // 3. Row dengan email kosong
        $data[] = [
            'name' => 'No Email Person',
            'email' => '',
            'company_name' => $companies[0],
        ];

        // 4. Row dengan email format invalid
        $data[] = [
            'name' => 'Bad Email',
            'email' => 'not-an-email',
            'company_name' => $companies[0],
        ];

        // 5. Row dengan company_name yang tidak ada di DB
        $data[] = [
            'name' => 'Wrong Company',
            'email' => "wrong-{$suffix}@example.com",
            'company_name' => 'Company Yang Tidak Ada PT XYZ',
        ];

        // 6. Row dengan company_name kosong
        $data[] = [
            'name' => 'Empty Company',
            'email' => "empty.company-{$suffix}@example.com",
            'company_name' => '',
        ];

        // 7. Row dengan multiple error sekaligus
        $data[] = [
            'name' => '',
            'email' => 'invalid',
            'company_name' => 'Tidak Ada Juga',
        ];

        // 8. Row duplicate email (sama dengan row #1 di array $data — yaitu valid1)
        $data[] = [
            'name' => 'Duplicate Email Row',
            'email' => "valid1-{$suffix}@example.com", // sama dengan valid1 di atas
            'company_name' => $companies[0],
        ];

        // 9. Sisa row valid sampai mencapai totalRows
        $extraNeeded = max(0, $totalRows - count($data));

        for ($i = 1; $i <= $extraNeeded; $i++) {
            $data[] = [
                'name' => $this->randomName(100 + $i),
                'email' => "extra{$i}-{$suffix}@example.com",
                'company_name' => $companies[array_rand($companies)],
            ];
        }

        return $data;
    }

    private function randomName(int $index): string
    {
        $firstNames = ['Andi', 'Budi', 'Citra', 'Dedi', 'Eka', 'Fajar', 'Gita', 'Hadi', 'Indra', 'Joko',
                       'Kartika', 'Lina', 'Maya', 'Nanda', 'Oka', 'Putri', 'Qori', 'Rizki', 'Sari', 'Tono'];
        $lastNames = ['Pratama', 'Santoso', 'Wijaya', 'Saputra', 'Lestari', 'Hidayat', 'Nugroho',
                      'Permata', 'Cahya', 'Anggraini'];

        $first = $firstNames[$index % count($firstNames)];
        $last = $lastNames[($index * 7) % count($lastNames)];

        return "{$first} {$last}";
    }
}
