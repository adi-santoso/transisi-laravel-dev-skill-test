<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreCompanyRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => 'required|string|max:255|unique:companies,name',
            'email' => 'required|email|max:255|unique:companies,email',
            'logo' => 'required|image|mimes:png|max:2048|dimensions:min_width=100,min_height=100',
            'website' => 'required|url|max:255',
        ];
    }
}
