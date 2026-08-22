<?php

use App\Models\Menu;
use App\Models\Transaction;
use App\Models\TransactionItem;
use Illuminate\Support\Facades\DB;

it('stores a transaction with server-side calculation and item snapshots', function () {
    $nasi = Menu::factory()->create(['name' => 'Nasi Goreng', 'price' => 25000]);
    $esTeh = Menu::factory()->create(['name' => 'Es Teh', 'price' => 5000]);

    $this->postJson('/transactions', [
        'items' => [
            ['menu_id' => $nasi->id, 'qty' => 2],
            ['menu_id' => $esTeh->id, 'qty' => 1],
        ],
        'discount' => 5000,
        'tax' => 1000,
        'payment_amount' => 100000,
        'cashier_name' => 'Budi',
    ])->assertCreated();

    $transaction = Transaction::query()->first();

    expect($transaction->subtotal)->toBe(55000)
        ->and($transaction->total)->toBe(51000)
        ->and($transaction->change_amount)->toBe(49000)
        ->and($transaction->cashier_name)->toBe('Budi')
        ->and($transaction->items)->toHaveCount(2);

    expect($transaction->items[0]->menu_name)->toBe('Nasi Goreng')
        ->and($transaction->items[0]->price)->toBe(25000)
        ->and($transaction->items[0]->subtotal)->toBe(50000);
});

it('rejects payment below the computed total', function () {
    $menu = Menu::factory()->create(['price' => 25000]);

    $this->postJson('/transactions', [
        'items' => [['menu_id' => $menu->id, 'qty' => 1]],
        'payment_amount' => 20000,
    ])->assertUnprocessable()
        ->assertJsonValidationErrors('payment_amount');
});

it('rejects an empty cart', function () {
    $this->postJson('/transactions', [
        'items' => [],
        'payment_amount' => 10000,
    ])->assertUnprocessable()
        ->assertJsonValidationErrors('items');
});

it('generates sequential daily receipt numbers', function () {
    $menu = Menu::factory()->create(['price' => 1000]);

    $this->postJson('/transactions', ['items' => [['menu_id' => $menu->id, 'qty' => 1]], 'payment_amount' => 1000])
        ->assertCreated();
    $this->postJson('/transactions', ['items' => [['menu_id' => $menu->id, 'qty' => 1]], 'payment_amount' => 1000])
        ->assertCreated();

    $numbers = Transaction::query()->orderBy('id')->pluck('receipt_number');

    $prefix = 'NOTA-'.now()->format('Ymd').'-';

    expect($numbers[0])->toBe($prefix.'0001')
        ->and($numbers[1])->toBe($prefix.'0002');
});

it('stores the transaction atomically when validation passes only once', function () {
    $menu = Menu::factory()->create(['price' => 10000]);

    $this->postJson('/transactions', [
        'items' => [
            ['menu_id' => $menu->id, 'qty' => 1],
            ['menu_id' => $menu->id + 999, 'qty' => 1],
        ],
        'payment_amount' => 50000,
    ])->assertUnprocessable();

    expect(Transaction::count())->toBe(0)
        ->and(TransactionItem::count())->toBe(0);
});

it('lists daily transactions with summary', function () {
    $today = Transaction::factory()->create(['total' => 51000]);
    Transaction::factory()->create(['total' => 7000]);
    TransactionItem::factory()->count(2)->create(['transaction_id' => $today->id]);

    $response = $this->getJson('/transactions')->assertSuccessful()->json();

    $entry = collect($response['data'])->firstWhere('receipt_number', $today->receipt_number);

    expect($response['summary']['count'])->toBe(2)
        ->and($response['summary']['total'])->toBe(58000)
        ->and($entry['items_count'])->toBe(2);
});

it('shows a transaction with its items', function () {
    $transaction = Transaction::factory()->create();
    TransactionItem::factory()->create([
        'transaction_id' => $transaction->id,
        'menu_name' => 'Ayam Geprek',
        'qty' => 3,
    ]);

    $this->getJson("/transactions/{$transaction->id}")
        ->assertSuccessful()
        ->assertJsonPath('data.receipt_number', $transaction->receipt_number)
        ->assertJsonPath('data.items.0.menu_name', 'Ayam Geprek')
        ->assertJsonPath('data.items.0.qty', 3);
});

it('keeps history intact after a menu is deleted', function () {
    $menu = Menu::factory()->create(['name' => 'Soto Ayam', 'price' => 18000]);

    $this->postJson('/transactions', [
        'items' => [['menu_id' => $menu->id, 'qty' => 1]],
        'payment_amount' => 20000,
    ])->assertCreated();

    $transaction = Transaction::query()->first();
    $menu->delete();

    expect($transaction->fresh()->items[0]->menu_name)->toBe('Soto Ayam')
        ->and($transaction->fresh()->items[0]->menu_id)->toBeNull();
});
