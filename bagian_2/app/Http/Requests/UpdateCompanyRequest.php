<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateCompanyRequest extends FormRequest
{
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
            'website' => 'required|url|max:255',
        ];
    }
}
