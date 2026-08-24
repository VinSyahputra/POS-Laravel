<?php

namespace App\Http\Controllers;

use App\Enums\Template;
use App\Http\Requests\StoreTransactionRequest;
use App\Models\Transaction;
use App\Services\ReceiptNumberService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class TransactionController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $date = $request->date('date') ?? now();

        $transactions = Transaction::query()
            ->whereDate('transaction_time', $date)
            ->withCount('items')
            ->orderByDesc('transaction_time')
            ->get();

        $summary = [
            'date' => $date->toDateString(),
            'count' => $transactions->count(),
            'total' => (int) $transactions->sum('total'),
        ];

        return response()->json(['data' => $transactions, 'summary' => $summary]);
    }

    public function show(Transaction $transaction): JsonResponse
    {
        return response()->json(['data' => $transaction->load('items')]);
    }

    public function store(StoreTransactionRequest $request): JsonResponse
    {
        $transaction = DB::transaction(function () use ($request) {
            $menus = $request->resolveMenus();

            $subtotal = $request->resolveSubtotal();
            $discount = (int) ($request->input('discount') ?? 0);
            $tax = (int) ($request->input('tax') ?? 0);
            $total = $request->resolveTotal();
            $paymentAmount = $request->integer('payment_amount');

            $transaction = Transaction::create([
                'receipt_number' => app(ReceiptNumberService::class)->generate(),
                'template' => $request->enum('template', Template::class) ?? Template::Foodcourt,
                'order_no' => $request->input('order_no'),
                'cashier_name' => $request->input('cashier_name'),
                'table_no' => $request->input('table_no'),
                'mode' => $request->input('mode') ?? 'DINE IN',
                'subtotal' => $subtotal,
                'discount' => $discount,
                'tax' => $tax,
                'total' => $total,
                'payment_amount' => $paymentAmount,
                'change_amount' => $paymentAmount - $total,
                'payment_method' => $request->input('payment_method') ?: 'CASH',
                'transaction_time' => $request->date('transaction_time') ?? now(),
                'entry_time' => $request->date('entry_time'),
            ]);

            foreach ($request->input('items') as $item) {
                $menu = $menus[$item['menu_id']];

                $transaction->items()->create([
                    'menu_id' => $menu->id,
                    'menu_name' => $menu->name,
                    'price' => $menu->price,
                    'qty' => (int) $item['qty'],
                    'subtotal' => $menu->price * (int) $item['qty'],
                ]);
            }

            return $transaction;
        });

        return response()->json(['data' => $transaction->load('items')], 201);
    }
}
