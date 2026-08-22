<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreMenuRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:150'],
            'price' => ['required', 'integer', 'min:0'],
            'category_id' => ['required', 'integer', 'exists:categories,id'],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'Nama menu wajib diisi.',
            'price.required' => 'Harga wajib diisi.',
            'price.integer' => 'Harga harus berupa angka bulat (rupiah).',
            'price.min' => 'Harga tidak boleh negatif.',
            'category_id.required' => 'Kategori wajib dipilih.',
            'category_id.exists' => 'Kategori tidak valid.',
        ];
    }
}
