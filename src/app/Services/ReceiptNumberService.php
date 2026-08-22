<?php

namespace App\Services;

use App\Models\Transaction;
use Illuminate\Support\Facades\DB;

class ReceiptNumberService
{
    public function generate(): string
    {
        return DB::transaction(function () {
            $prefix = 'NOTA-'.now()->format('Ymd').'-';

            $latest = Transaction::query()
                ->where('receipt_number', 'like', $prefix.'%')
                ->orderByDesc('receipt_number')
                ->lockForUpdate()
                ->first();

            $sequence = $latest ? ((int) substr($latest->receipt_number, -4)) + 1 : 1;

            return $prefix.str_pad((string) $sequence, 4, '0', STR_PAD_LEFT);
        });
    }
}
