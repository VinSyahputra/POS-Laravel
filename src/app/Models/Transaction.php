<?php

namespace App\Models;

use App\Enums\Template;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Transaction extends Model
{
    use HasFactory;

    protected $fillable = [
        'receipt_number',
        'template',
        'order_no',
        'generated_no',
        'cashier_name',
        'table_no',
        'mode',
        'subtotal',
        'discount',
        'tax',
        'total',
        'payment_amount',
        'change_amount',
        'payment_method',
        'transaction_time',
        'entry_time',
    ];

    protected function casts(): array
    {
        return [
            'subtotal' => 'integer',
            'discount' => 'integer',
            'tax' => 'integer',
            'total' => 'integer',
            'payment_amount' => 'integer',
            'change_amount' => 'integer',
            'transaction_time' => 'datetime',
            'entry_time' => 'datetime',
            'mode' => 'string',
            'template' => Template::class,
        ];
    }

    public function items(): HasMany
    {
        return $this->hasMany(TransactionItem::class);
    }
}
