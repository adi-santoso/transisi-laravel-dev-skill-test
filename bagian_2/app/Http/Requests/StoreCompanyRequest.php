<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class StoreCompanyRequest extends FormRequest
{
    /**
     * Regex untuk validasi nama file tmp logo.
     * Format: UUID v4 (36 chars) + ekstensi .png
     * Contoh: 550e8400-e29b-41d4-a716-446655440000.png
     */
    public const TMP_LOGO_REGEX = '/^[a-f0-9]{8}-[a-f0-9]{4}-[a-f0-9]{4}-[a-f0-9]{4}-[a-f0-9]{12}\.png$/';

    protected ?string $tmpLogoToFlash = null;

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        // Kalau ada tmp_logo valid (file masih ada di storage/app/tmp),
        // logo input tidak required — user bisa submit tanpa upload ulang.
        $hasValidTmpLogo = $this->hasValidTmpLogo();

        return [
            'name' => 'required|string|max:255|unique:companies,name',
            'email' => 'required|email|max:255|unique:companies,email',
            'logo' => [
                $hasValidTmpLogo ? 'nullable' : 'required',
                'image',
                'mimes:png',
                'max:2048',
                'dimensions:min_width=100,min_height=100',
            ],
            'tmp_logo' => [
                'nullable',
                'string',
                'regex:' . self::TMP_LOGO_REGEX,
            ],
            'website' => 'required|url|max:255',
        ];
    }

    /**
     * Cek apakah tmp_logo di request ada dan file fisiknya benar-benar exist.
     * Path traversal di-block oleh regex format UUID strict.
     */
    public function hasValidTmpLogo(): bool
    {
        $tmpLogo = $this->input('tmp_logo');

        if (!$tmpLogo || !is_string($tmpLogo)) {
            return false;
        }

        // Regex strict: hanya UUID + .png. Tidak ada `..`, `/`, `\`.
        if (!preg_match(self::TMP_LOGO_REGEX, $tmpLogo)) {
            return false;
        }

        return Storage::disk('local')->exists('tmp/' . $tmpLogo);
    }

    /**
     * Setelah validation jalan, kalau:
     *  - file logo SENDIRI valid (tidak ada error di field 'logo'), DAN
     *  - field LAIN ada error (validator->fails() = true)
     * Maka simpan logo ke storage/app/tmp/ dengan UUID filename,
     * dan flash tmp_logo ke old() supaya muncul di form re-render.
     */
    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            // Hanya jalan kalau ada file logo BARU di-upload di request ini.
            if (!$this->hasFile('logo')) {
                return;
            }

            // Kalau logo sendiri invalid (mimes salah, terlalu besar, dll), jangan save.
            if ($validator->errors()->has('logo')) {
                return;
            }

            // Kalau tidak ada error sama sekali, biarkan flow normal jalan (no need tmp).
            if ($validator->errors()->isEmpty()) {
                return;
            }

            // Kondisi: logo VALID, field lain FAIL → save tmp logo.
            $file = $this->file('logo');
            if (!$file || !$file->isValid()) {
                return;
            }

            // Hapus tmp_logo lama (kalau user re-upload setelah submit gagal sebelumnya).
            $oldTmp = $this->input('tmp_logo');
            if ($oldTmp && preg_match(self::TMP_LOGO_REGEX, $oldTmp)) {
                Storage::disk('local')->delete('tmp/' . $oldTmp);
            }

            // Simpan dengan UUID filename agar tidak collision + tidak expose nama asli.
            $filename = Str::uuid()->toString() . '.png';
            $file->storeAs('tmp', $filename, 'local');

            $this->tmpLogoToFlash = $filename;
        });
    }

    protected function failedValidation(Validator $validator)
    {
        if ($this->tmpLogoToFlash) {
            $response = redirect($this->getRedirectUrl())
                ->withInput(array_merge($this->except('logo'), ['tmp_logo' => $this->tmpLogoToFlash]))
                ->withErrors($validator, $this->errorBag);

            throw new ValidationException($validator, $response);
        }

        parent::failedValidation($validator);
    }
}
