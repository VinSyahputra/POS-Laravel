<?php

namespace App\Http\Requests;

use App\Enums\Template;
use App\Models\Menu;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class StoreTransactionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'items' => ['required', 'array', 'min:1'],
            'items.*.menu_id' => ['required', 'integer', 'exists:menus,id'],
            'items.*.qty' => ['required', 'integer', 'min:1', 'max:1000'],
            'discount' => ['nullable', 'integer', 'min:0'],
            'tax' => ['nullable', 'integer', 'min:0'],
            'payment_amount' => ['required', 'integer', 'min:0'],
            'payment_method' => ['nullable', 'string', 'max:30'],
            'cashier_name' => ['nullable', 'string', 'max:100'],
            'order_no' => ['nullable', 'string', 'max:50'],
            'template' => ['nullable', Rule::enum(Template::class)],
            'table_no' => ['nullable', 'string', 'max:20'],
            'mode' => ['nullable', 'in:TAKEAWAY,DINE IN'],
            'transaction_time' => ['nullable', 'date'],
            'entry_time' => ['nullable', 'date'],
        ];
    }

    public function messages(): array
    {
        return [
            'items.required' => 'Keranjang kosong.',
            'items.min' => 'Keranjang kosong.',
            'items.*.menu_id.exists' => 'Ada menu yang tidak valid.',
            'items.*.qty.min' => 'Qty minimal 1.',
            'payment_amount.required' => 'Jumlah pembayaran wajib diisi.',
        ];
    }

    public function after(): array
    {
        return [
            function (Validator $validator) {
                if ($validator->errors()->any()) {
                    return;
                }

                $total = $this->resolveTotal();

                if ($this->integer('payment_amount') < $total) {
                    $validator->errors()->add(
                        'payment_amount',
                        "Pembayaran kurang dari total tagihan (Rp {$total})."
                    );
                }
            },
        ];
    }

    public function resolveSubtotal(): int
    {
        $menus = $this->resolveMenus();

        return collect($this->input('items'))
            ->sum(fn (array $item) => ($menus[$item['menu_id']]->price ?? 0) * (int) $item['qty']);
    }

    public function resolveTotal(): int
    {
        $subtotal = $this->resolveSubtotal();
        $discount = (int) ($this->input('discount') ?? 0);
        $tax = (int) ($this->input('tax') ?? 0);

        return max(0, $subtotal - $discount) + $tax;
    }

    /**
     * @return array<int, Menu>
     */
    public function resolveMenus(): array
    {
        $menuIds = collect($this->input('items', []))->pluck('menu_id')->unique()->all();

        return Menu::query()
            ->whereIn('id', $menuIds)
            ->get()
            ->keyBy('id')
            ->all();
    }
}
