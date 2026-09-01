<?php

namespace App\Http\Requests;

use App\Enums\Template;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class GenerateMenuComboRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'target' => ['required', 'integer', 'min:0', 'max:2000000'],
            'template' => ['required', Rule::enum(Template::class)],
            'max_qty_per_item' => ['nullable', 'integer', 'min:1', 'max:30'],
        ];
    }

    public function messages(): array
    {
        return [
            'target.required' => 'Target budget wajib diisi.',
            'target.integer' => 'Target budget harus berupa angka bulat (rupiah).',
            'target.min' => 'Target budget tidak boleh negatif.',
            'target.max' => 'Target budget maksimal Rp 2.000.000 per generate.',
            'template.required' => 'Outlet wajib dipilih.',
            'template.enum' => 'Outlet tidak valid.',
            'max_qty_per_item.integer' => 'Batas qty per item harus berupa angka bulat.',
            'max_qty_per_item.min' => 'Batas qty per item minimal 1.',
            'max_qty_per_item.max' => 'Batas qty per item maksimal 30.',
        ];
    }

    public function maxQtyPerItem(): int
    {
        return $this->integer('max_qty_per_item', 10);
    }
}
