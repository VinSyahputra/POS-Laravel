@extends('layouts.app')

@section('content')
<div x-data="budgetGenerator" class="mx-auto flex h-full max-w-2xl flex-col gap-4 overflow-y-auto p-4 md:p-6">

    <div class="flex items-center justify-between">
        <h1 class="text-xl font-bold md:text-2xl">Generate Nota dari Budget</h1>
        <a href="/" class="rounded-xl bg-slate-200 px-4 py-2 text-sm font-semibold text-slate-600 hover:bg-slate-300">← Kembali</a>
    </div>

    <template x-if="error">
        <div class="rounded-xl bg-red-100 px-4 py-3 text-sm font-medium text-red-700" x-text="error"></div>
    </template>

    <div class="rounded-2xl bg-white p-5 shadow">
        @include('partials.order-header-form')
    </div>

    <div class="rounded-2xl bg-white p-5 shadow">
        <div class="grid grid-cols-2 gap-3">
            <div class="col-span-2">
                <label class="mb-1 block text-xs font-semibold text-slate-500">Target Budget (Rp)</label>
                <input type="number" x-model.number="targetBudget" min="0" step="1" placeholder="mis. 500000"
                    class="w-full rounded-xl border border-slate-300 px-3 py-2.5 text-sm font-semibold focus:border-orange-500 focus:outline-none">
            </div>
            <div class="col-span-2">
                <label class="mb-1 block text-xs font-semibold text-slate-500">Batas Qty per Item</label>
                <input type="number" x-model.number="maxQtyPerItem" min="1" max="30" step="1"
                    class="w-full rounded-xl border border-slate-300 px-3 py-2.5 text-sm focus:border-orange-500 focus:outline-none">
            </div>
        </div>

        <button @click="generate()" :disabled="generating"
            class="mt-4 w-full rounded-xl bg-orange-500 px-5 py-3 text-sm font-semibold text-white shadow hover:bg-orange-600 disabled:opacity-50">
            <span x-show="!generating">Generate</span>
            <span x-show="generating" x-cloak>Menghitung…</span>
        </button>

        <template x-if="generateFailed">
            <div class="mt-4 rounded-xl bg-amber-50 px-4 py-3 text-sm font-medium text-amber-700">
                <p x-text="generateError"></p>
            </div>
        </template>
    </div>

    <div class="rounded-2xl bg-white p-5 shadow">
        <h2 class="text-lg font-bold">Hasil Generate</h2>

        <template x-if="items.length === 0 && !generateFailed">
            <p class="mt-2 text-sm text-slate-400">Belum ada hasil. Isi target budget lalu tekan Generate.</p>
        </template>

        <template x-if="items.length > 0">
            <div class="mt-3 space-y-2">
                <template x-for="item in items" :key="item.menu_id">
                    <div class="flex items-center justify-between rounded-xl bg-slate-50 px-3 py-2">
                        <div>
                            <p class="text-sm font-semibold" x-text="item.name"></p>
                            <p class="text-xs text-slate-500" x-text="'Rp ' + rupiah(item.price) + ' x ' + item.qty + ' = Rp ' + rupiah(itemSubtotal(item))"></p>
                        </div>
                        <div class="flex items-center gap-2">
                            <button @click="decrease(item.menu_id)" class="h-7 w-7 rounded-full bg-slate-200 text-sm font-bold text-slate-600">−</button>
                            <span class="w-6 text-center text-sm font-semibold" x-text="item.qty"></span>
                            <button @click="increase(item.menu_id)" class="h-7 w-7 rounded-full bg-slate-200 text-sm font-bold text-slate-600">+</button>
                            <button @click="removeItem(item.menu_id)" class="ml-1 rounded-lg bg-red-100 px-2 py-1 text-xs font-semibold text-red-600 hover:bg-red-200">Hapus</button>
                        </div>
                    </div>
                </template>

                <div class="mt-3 rounded-xl bg-slate-900 px-4 py-3 text-sm text-white">
                    <div class="flex justify-between"><span>Subtotal</span><span class="font-bold" x-text="'Rp ' + rupiah(subtotal)"></span></div>
                    <div class="flex justify-between text-slate-300"><span>Target</span><span x-text="'Rp ' + rupiah(targetBudget)"></span></div>
                    <div class="flex justify-between text-slate-300"><span>Selisih</span><span x-text="'Rp ' + rupiah(difference)"></span></div>
                </div>
            </div>
        </template>

        <div class="mt-4">
            <label class="mb-1 block text-xs font-semibold text-slate-500">Cari menu untuk tambah manual</label>
            <input type="text" x-model.debounce.300ms="menuSearch" placeholder="Cari menu…"
                class="w-full rounded-xl border border-slate-300 px-3 py-2.5 text-sm focus:border-orange-500 focus:outline-none">
            <div class="mt-2 max-h-48 overflow-y-auto rounded-xl border border-slate-100">
                <template x-if="loading">
                    <p class="p-3 text-center text-sm text-slate-400">Memuat menu…</p>
                </template>
                <template x-for="menu in filteredMenus" :key="menu.id">
                    <button @click="addToCart(menu)"
                        class="flex w-full items-center justify-between border-b border-slate-50 px-3 py-2 text-left text-sm hover:bg-slate-50">
                        <span x-text="menu.name"></span>
                        <span class="text-slate-500" x-text="'Rp ' + rupiah(menu.price)"></span>
                    </button>
                </template>
            </div>
        </div>
    </div>

    <div class="rounded-2xl bg-white p-5 shadow">
        <label class="mb-1 block text-xs font-semibold text-slate-500">Dibayar (Rp)</label>
        <input type="number" x-model.number="paymentAmount" min="0" step="1" placeholder="0"
            class="w-full rounded-xl border border-slate-300 px-3 py-3 text-base font-bold focus:border-orange-500 focus:outline-none">

        <button @click="submitTransaction()" :disabled="!canSubmit"
            class="mt-4 w-full rounded-xl bg-slate-900 px-5 py-3 text-sm font-bold text-white shadow hover:bg-slate-800 disabled:bg-slate-300">
            <span x-show="!submitting">Submit Nota</span>
            <span x-show="submitting" x-cloak>Menyimpan…</span>
        </button>
    </div>

    <div x-show="lastTransaction" x-cloak class="fixed inset-0 z-50 flex items-center justify-center bg-black/50">
        <div class="w-full max-w-sm rounded-2xl bg-white p-6 text-center shadow-xl">
            <div class="text-4xl">✅</div>
            <h2 class="mt-2 text-lg font-bold">Transaksi Berhasil</h2>
            <p class="mt-1 text-sm text-slate-500" x-text="lastTransaction?.receipt_number"></p>
            <div class="mt-4 rounded-xl bg-slate-50 p-4 text-left text-sm">
                <div class="flex justify-between"><span>Total</span>
                    <span class="font-bold" x-text="'Rp ' + rupiah(lastTransaction?.total)"></span></div>
                <div class="flex justify-between"><span>Dibayar</span>
                    <span x-text="'Rp ' + rupiah(lastTransaction?.payment_amount)"></span></div>
                <div class="flex justify-between text-green-700"><span>Kembalian</span>
                    <span class="font-extrabold" x-text="'Rp ' + rupiah(lastTransaction?.change_amount)"></span></div>
            </div>
            <template x-if="$store.printing.notice">
                <p class="mt-3 rounded-xl bg-green-50 px-4 py-2 text-sm font-semibold text-green-700" x-text="$store.printing.notice"></p>
            </template>
            <template x-if="$store.printing.error">
                <p class="mt-3 rounded-xl bg-red-50 px-4 py-2 text-sm font-semibold text-red-700" x-text="$store.printing.error"></p>
            </template>
            <div class="mt-5 flex gap-3">
                <button @click="closeReceipt()"
                    class="flex-1 rounded-xl bg-slate-200 px-4 py-3 text-sm font-bold text-slate-600">Tutup</button>
                <button @click="$store.printing.printReceipt(lastTransaction)" :disabled="$store.printing.printing"
                    class="flex-1 rounded-xl bg-slate-900 px-4 py-3 text-sm font-bold text-white shadow hover:bg-slate-800 disabled:bg-slate-400"
                    x-text="$store.printing.printing ? 'Mencetak…' : '🖨️ Cetak Nota'"></button>
            </div>
        </div>
    </div>

    <div x-show="$store.printing.showPreview" x-cloak class="fixed inset-0 z-[70] flex items-center justify-center bg-black/50">
        <div class="mx-4 w-full max-w-md rounded-2xl bg-white p-5 shadow-xl md:p-6">
            <h2 class="text-lg font-bold">Preview Nota</h2>
            <p class="mt-1 text-xs text-red-600" x-text="$store.printing.error"></p>
            <pre class="mt-3 max-h-80 overflow-auto rounded-xl bg-slate-900 p-4 font-mono text-xs leading-relaxed text-green-300"
                x-text="$store.printing.previewText"></pre>
            <p class="mt-2 text-xs text-slate-400">Cetak via Bluetooth gagal. Pastikan printer menyala &amp; sudah dipasangkan (tab Printer), lalu coba cetak ulang.</p>
            <div class="mt-5 flex gap-3">
                <button @click="$store.printing.showPreview = false"
                    class="flex-1 rounded-xl bg-slate-200 px-4 py-3 text-sm font-bold text-slate-600">Tutup</button>
                <button @click="$store.printing.reprint()" :disabled="$store.printing.printing"
                    class="flex-1 rounded-xl bg-orange-500 px-4 py-3 text-sm font-bold text-white shadow hover:bg-orange-600 disabled:bg-slate-400"
                    x-text="$store.printing.printing ? 'Mencetak…' : '🖨️ Cetak Ulang'"></button>
            </div>
        </div>
    </div>
</div>
@endsection
