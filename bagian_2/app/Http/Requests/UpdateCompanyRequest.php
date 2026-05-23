<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Illuminate\Validation\Rule;

class UpdateCompanyRequest extends FormRequest
{
    protected ?string $tmpLogoToFlash = null;

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $companyId = $this->route('company')?->id;

        return [
            'name' => [
                'required', 'string', 'max:255',
                Rule::unique('companies', 'name')->ignore($companyId),
            ],
            'email' => [
                'required', 'email', 'max:255',
                Rule::unique('companies', 'email')->ignore($companyId),
            ],
            'logo' => 'nullable|image|mimes:png|max:2048|dimensions:min_width=100,min_height=100',
            'tmp_logo' => [
                'nullable',
                'string',
                'regex:' . StoreCompanyRequest::TMP_LOGO_REGEX,
            ],
            'website' => 'required|url|max:255',
        ];
    }

    /**
     * Cek apakah tmp_logo di request ada dan file fisiknya benar-benar exist.
     * Same logic dengan Store — re-use untuk konsistensi.
     */
    public function hasValidTmpLogo(): bool
    {
        $tmpLogo = $this->input('tmp_logo');

        if (!$tmpLogo || !is_string($tmpLogo)) {
            return false;
        }

        if (!preg_match(StoreCompanyRequest::TMP_LOGO_REGEX, $tmpLogo)) {
            return false;
        }

        return Storage::disk('local')->exists('tmp/' . $tmpLogo);
    }

    /**
     * Sama logic-nya dengan StoreCompanyRequest::withValidator().
     * Bedanya: di Update, logo SELALU nullable. Tapi kalau user upload file baru
     * dan field lain fail, kita tetap save tmp supaya user tidak kehilangan upload.
     */
    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            if (!$this->hasFile('logo')) {
                return;
            }

            if ($validator->errors()->has('logo')) {
                return;
            }

            if ($validator->errors()->isEmpty()) {
                return;
            }

            $file = $this->file('logo');
            if (!$file || !$file->isValid()) {
                return;
            }

            // Cleanup tmp lama kalau ada.
            $oldTmp = $this->input('tmp_logo');
            if ($oldTmp && preg_match(StoreCompanyRequest::TMP_LOGO_REGEX, $oldTmp)) {
                Storage::disk('local')->delete('tmp/' . $oldTmp);
            }

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
