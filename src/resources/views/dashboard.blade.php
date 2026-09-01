@extends('layouts.app')

@section('content')
<div x-data class="flex h-full">
    @include('partials.sidebar-nav')

    <main class="h-full flex-1 overflow-y-auto p-4 md:p-6">
        <div class="mb-3 flex items-center gap-3 md:hidden">
            <button @click="$store.ui.sidebarOpen = true"
                class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl bg-slate-900 text-white shadow hover:bg-slate-800">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M4 6h16M4 12h16M4 18h16" />
                </svg>
            </button>
            <span class="text-sm font-bold text-slate-600"
                x-text="$store.ui.tab === 'cashier' ? 'Kasir' : ($store.ui.tab === 'menu' ? 'Food Court Menu' : ($store.ui.tab === 'history' ? 'Riwayat Transaksi' : 'Pengaturan Printer'))"></span>
        </div>

        <section x-show="$store.ui.tab === 'cashier'" x-cloak class="h-full">
            <div x-data="cashier" class="flex h-full flex-col gap-4 lg:flex-row lg:gap-6">
                <div class="flex min-w-0 flex-1 flex-col">
                    <template x-if="error">
                        <div class="mb-4 rounded-xl bg-red-100 px-4 py-3 text-sm font-medium text-red-700" x-text="error"></div>
                    </template>

                    <div class="relative">
                        <span class="pointer-events-none absolute inset-y-0 left-4 flex items-center text-slate-400">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-4.35-4.35M17 11a6 6 0 11-12 0 6 6 0 0112 0z" />
                            </svg>
                        </span>
                        <input type="text" x-model.debounce.300ms="search" placeholder="Cari menu makanan…"
                            class="w-full rounded-xl border border-slate-200 bg-white py-3.5 pl-12 pr-10 text-sm font-semibold shadow placeholder:font-normal placeholder:text-slate-400 focus:border-orange-500 focus:outline-none">
                        <button x-show="search" @click="search = ''" x-cloak
                            class="absolute inset-y-0 right-3 flex items-center text-slate-400 hover:text-slate-600">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </button>
                    </div>

                    <div class="mt-4 flex flex-wrap gap-2">
                        <button @click="filterCategoryId = ''"
                            :class="filterCategoryId === '' ? 'bg-slate-900 text-white' : 'bg-white text-slate-600 shadow'"
                            class="rounded-xl px-5 py-3 text-sm font-semibold">Semua</button>
                        <template x-for="category in categories" :key="category.id">
                            <button @click="filterCategoryId = category.id"
                                :class="filterCategoryId === category.id ? 'bg-slate-900 text-white' : 'bg-white text-slate-600 shadow'"
                                class="rounded-xl px-5 py-3 text-sm font-semibold" x-text="category.name"></button>
                        </template>
                    </div>

                    <div class="mt-4 grid flex-1 grid-cols-2 content-start gap-3 overflow-y-auto pr-1 md:grid-cols-3 xl:grid-cols-4">
                        <template x-if="loading">
                            <div class="col-span-full py-10 text-center text-slate-400">Memuat menu…</div>
                        </template>
                        <template x-if="!loading && filteredMenus.length === 0">
                            <div class="col-span-full py-10 text-center text-slate-400">Tidak ada menu yang cocok.</div>
                        </template>
                        <template x-for="menu in filteredMenus" :key="menu.id">
                            <button @click="addToCart(menu)"
                                class="flex min-h-28 flex-col justify-between rounded-2xl bg-white p-4 text-left shadow transition hover:-translate-y-0.5 hover:shadow-lg active:translate-y-0">
                                <div>
                                    <span class="rounded-full bg-orange-100 px-2.5 py-1 text-xs font-semibold text-orange-600"
                                        x-text="menu.category?.name ?? '-'"></span>
                                    <div class="mt-2 text-base font-bold leading-tight" x-text="menu.name"></div>
                                </div>
                                <div class="text-lg font-extrabold text-orange-600" x-text="'Rp ' + rupiah(menu.price)"></div>
                            </button>
                        </template>
                    </div>
                </div>

                <aside class="mb-4 flex w-full shrink-0 flex-col rounded-2xl bg-white shadow lg:mb-0 lg:w-80"
                    :class="lastTransaction || cartExpanded ? '' : 'max-h-[55vh] self-end overflow-hidden lg:max-h-none lg:self-auto'">
                    <button @click="cartExpanded = !cartExpanded"
                        class="flex items-center justify-between border-b border-slate-100 px-5 py-4 lg:hidden">
                        <span class="flex items-center gap-2 text-lg font-bold">
                            Keranjang
                            <span x-show="cartCount > 0" x-cloak
                                class="rounded-full bg-orange-500 px-2.5 py-0.5 text-xs font-bold text-white"
                                x-text="cartCount"></span>
                        </span>
                        <span class="flex items-center gap-3">
                            <span class="font-extrabold" x-text="'Rp ' + rupiah(cartSubtotal)"></span>
                            <span class="text-sm font-bold text-slate-400" x-text="cartExpanded ? '▼' : '▲'"></span>
                        </span>
                    </button>

                    <div class="hidden items-center justify-between border-b border-slate-100 px-5 py-4 lg:flex">
                        <h2 class="text-lg font-bold">Detail Pesanan</h2>
                    </div>

                    <div class="border-b border-slate-100 px-5 py-4" :class="cartExpanded ? '' : 'hidden lg:block'">
                        @include('partials.order-header-form')
                    </div>

                    <div class="px-5 py-4" :class="cartExpanded ? '' : 'hidden lg:block'">
                        <div>
                            <label class="mb-1 block text-xs font-semibold text-slate-500">Dibayar (Rp)</label>
                            <input type="number" x-model.number="paymentAmount" min="0" step="1" placeholder="0"
                                class="w-full rounded-xl border border-slate-300 px-3 py-3 text-base font-bold focus:border-orange-500 focus:outline-none">
                        </div>

                        <div class="mt-3 flex items-center justify-between rounded-xl bg-green-50 px-4 py-3">
                            <span class="text-sm font-semibold text-green-700">Kembalian</span>
                            <span class="text-lg font-extrabold text-green-700" x-text="'Rp ' + rupiah(changeAmount)"></span>
                        </div>

                        <div class="mt-3 flex items-center justify-between text-lg font-extrabold">
                            <span>Subtotal</span>
                            <span x-text="'Rp ' + rupiah(cartSubtotal)"></span>
                        </div>

                        <div class="mt-4">
                            <button @click="submitTransaction()" :disabled="!canSubmit"
                                class="w-full rounded-xl bg-orange-500 px-5 py-4 text-base font-bold text-white shadow hover:bg-orange-600 disabled:cursor-not-allowed disabled:bg-slate-300"
                                x-text="submitting ? 'Menyimpan…' : '🖨️ Cetak'"></button>
                        </div>
                    </div>
                </aside>

                <aside class="mb-4 flex w-full shrink-0 flex-col rounded-2xl bg-white shadow lg:mb-0 lg:w-80"
                    :class="lastTransaction || cartExpanded ? '' : 'hidden lg:flex'">
                    <div class="flex items-center justify-between border-b border-slate-100 px-5 py-4">
                        <h2 class="text-lg font-bold">Item Dipilih
                            <span x-show="cartCount > 0" x-cloak
                                class="ml-1 rounded-full bg-orange-500 px-2.5 py-0.5 text-xs font-bold text-white"
                                x-text="cartCount"></span>
                        </h2>
                        <button x-show="cart.length > 0" @click="clearCart()" x-cloak
                            class="rounded-lg bg-red-100 px-3 py-2 text-xs font-semibold text-red-600 hover:bg-red-200">Kosongkan</button>
                    </div>

                    <div class="flex-1 overflow-y-auto px-5 py-4">
                        <template x-if="cart.length === 0">
                            <p class="py-10 text-center text-sm text-slate-400">Klik menu di sebelah kiri untuk menambah item.</p>
                        </template>
                        <div class="space-y-3 pb-4">
                            <template x-for="item in cart" :key="item.menu_id">
                                <div class="rounded-xl border border-slate-100 p-3">
                                    <div class="flex items-start justify-between gap-2">
                                        <div class="text-sm font-bold" x-text="item.name"></div>
                                        <div class="flex shrink-0 items-center gap-1">
                                            <button @click="moveUp(item.menu_id)" title="Naikkan urutan"
                                                class="rounded-lg bg-slate-100 px-2 py-1 text-xs font-bold text-slate-600 hover:bg-slate-200">▲</button>
                                            <button @click="moveDown(item.menu_id)" title="Turunkan urutan"
                                                class="rounded-lg bg-slate-100 px-2 py-1 text-xs font-bold text-slate-600 hover:bg-slate-200">▼</button>
                                            <button @click="removeItem(item.menu_id)"
                                                class="rounded-lg bg-red-50 px-2 py-1 text-xs font-bold text-red-500 hover:bg-red-100">✕</button>
                                        </div>
                                    </div>
                                    <div class="mt-2 flex items-center justify-between">
                                        <div class="flex items-center gap-2">
                                            <button @click="decrease(item.menu_id)"
                                                class="h-10 w-10 rounded-xl bg-slate-100 text-lg font-bold text-slate-600 hover:bg-slate-200">−</button>
                                            <span class="w-8 text-center text-base font-bold" x-text="item.qty"></span>
                                            <button @click="increase(item.menu_id)"
                                                class="h-10 w-10 rounded-xl bg-orange-100 text-lg font-bold text-orange-600 hover:bg-orange-200">+</button>
                                        </div>
                                        <div class="text-right text-sm">
                                            <div class="text-xs text-slate-400" x-text="'@Rp ' + rupiah(item.price)"></div>
                                            <div class="font-bold" x-text="'Rp ' + rupiah(itemSubtotal(item))"></div>
                                        </div>
                                    </div>
                                </div>
                            </template>
                        </div>
                    </div>
                </aside>

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
            </div>
        </section>

        <section x-show="$store.ui.tab === 'menu'" x-cloak class="h-full">
            <div x-data="menuManager" class="flex h-full flex-col">
                <div class="flex flex-wrap items-center justify-between gap-3">
                    <h1 class="text-xl font-bold md:text-2xl">Food Court Menu</h1>
                    <div class="flex gap-2">
                        <button @click="switchSubTab('categories')"
                            :class="subTab === 'categories' ? 'bg-slate-900 text-white' : 'bg-white text-slate-600'"
                            class="rounded-xl px-5 py-3 text-sm font-semibold shadow">Kategori</button>
                        <button @click="switchSubTab('menus')"
                            :class="subTab === 'menus' ? 'bg-slate-900 text-white' : 'bg-white text-slate-600'"
                            class="rounded-xl px-5 py-3 text-sm font-semibold shadow">Daftar Menu</button>
                    </div>
                </div>

                <template x-if="error">
                    <div class="mt-4 rounded-xl bg-red-100 px-4 py-3 text-sm font-medium text-red-700" x-text="error"></div>
                </template>
                <template x-if="notice">
                    <div class="mt-4 rounded-xl bg-green-100 px-4 py-3 text-sm font-medium text-green-700" x-text="notice"></div>
                </template>

                <div x-show="subTab === 'categories'" class="mt-4 flex-1 overflow-y-auto">
                    <div class="mb-4">
                        <button x-show="!showCategoryForm" @click="showCategoryForm = true"
                            class="rounded-xl bg-orange-500 px-5 py-3 text-sm font-semibold text-white shadow hover:bg-orange-600">
                            + Tambah Kategori
                        </button>
                        <form x-show="showCategoryForm" @submit.prevent="saveCategory()" class="flex flex-wrap items-center gap-3">
                            <input type="text" x-model="categoryForm.name" placeholder="Nama kategori (mis. Makanan)"
                                class="rounded-xl border border-slate-300 px-4 py-3 text-sm focus:border-orange-500 focus:outline-none">
                            <button type="submit"
                                class="rounded-xl bg-orange-500 px-5 py-3 text-sm font-semibold text-white hover:bg-orange-600">Simpan</button>
                            <button type="button" @click="cancelCategoryForm()"
                                class="rounded-xl bg-slate-200 px-5 py-3 text-sm font-semibold text-slate-600">Batal</button>
                        </form>
                    </div>

                    <div class="overflow-x-auto rounded-2xl bg-white shadow">
                        <table class="w-full text-left text-sm">
                            <thead class="bg-slate-50 text-xs uppercase tracking-wide text-slate-500">
                                <tr>
                                    <th class="px-6 py-4">Nama Kategori</th>
                                    <th class="px-6 py-4">Jumlah Menu</th>
                                    <th class="px-6 py-4 text-right">Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                <template x-if="loading">
                                    <tr><td colspan="3" class="px-6 py-8 text-center text-slate-400">Memuat…</td></tr>
                                </template>
                                <template x-if="!loading && categories.length === 0">
                                    <tr><td colspan="3" class="px-6 py-8 text-center text-slate-400">Belum ada kategori.</td></tr>
                                </template>
                                <template x-for="category in categories" :key="category.id">
                                    <tr class="border-t border-slate-100">
                                        <td class="px-6 py-4 font-semibold" x-text="category.name"></td>
                                        <td class="px-6 py-4">
                                            <span class="rounded-full bg-slate-100 px-3 py-1 text-xs font-semibold text-slate-600"
                                                x-text="category.menus_count + ' menu'"></span>
                                        </td>
                                        <td class="px-6 py-4 text-right">
                                            <button @click="editCategory(category)"
                                                class="rounded-lg bg-slate-100 px-4 py-2 text-xs font-semibold text-slate-600 hover:bg-slate-200">Edit</button>
                                            <button @click="destroyCategory(category)"
                                                class="ml-2 rounded-lg bg-red-100 px-4 py-2 text-xs font-semibold text-red-600 hover:bg-red-200">Hapus</button>
                                        </td>
                                    </tr>
                                </template>
                            </tbody>
                        </table>
                    </div>
                </div>

                <div x-show="subTab === 'menus'" x-cloak class="mt-4 flex-1 overflow-y-auto">
                    <div class="mb-4 flex flex-wrap items-center gap-3">
                        <button @click="editMenu({ id: null, name: '', price: '', category_id: categories[0]?.id ?? '' }); showMenuForm = true"
                            class="rounded-xl bg-orange-500 px-5 py-3 text-sm font-semibold text-white shadow hover:bg-orange-600">
                            + Tambah Menu
                        </button>
                        <div class="relative min-w-[220px] flex-1 sm:max-w-xs">
                            <span class="pointer-events-none absolute inset-y-0 left-4 flex items-center text-slate-400">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-4.35-4.35M17 11a6 6 0 11-12 0 6 6 0 0112 0z" />
                                </svg>
                            </span>
                            <input type="text" x-model.debounce.300ms="menuSearch" placeholder="Cari nama menu…"
                                class="w-full rounded-xl border border-slate-300 py-3 pl-12 pr-4 text-sm focus:border-orange-500 focus:outline-none">
                        </div>
                        <select x-model.number="menuFilterCategoryId" @change="filterMenus()"
                            class="rounded-xl border border-slate-300 bg-white px-4 py-3 text-sm focus:border-orange-500 focus:outline-none">
                            <option value="">Semua Kategori</option>
                            <template x-for="category in categories" :key="category.id">
                                <option :value="category.id" x-text="category.name"></option>
                            </template>
                        </select>
                        <select x-model="menuFilterTemplate" @change="filterMenus()"
                            class="rounded-xl border border-slate-300 bg-white px-4 py-3 text-sm focus:border-orange-500 focus:outline-none">
                            <option value="">Semua Outlet</option>
                            <option value="FOODCOURT">Foodcourt</option>
                            <option value="PASTRY_BAKERY">Pastry &amp; Bakery</option>
                            <option value="CAFE_1912">Cafe 1912</option>
                        </select>
                    </div>

                    <div class="overflow-x-auto rounded-2xl bg-white shadow">
                        <table class="w-full text-left text-sm">
                            <thead class="bg-slate-50 text-xs uppercase tracking-wide text-slate-500">
                                <tr>
                                    <th class="px-6 py-4">Nama Menu</th>
                                    <th class="px-6 py-4">Kategori</th>
                                    <th class="px-6 py-4">Outlet</th>
                                    <th class="px-6 py-4">Harga</th>
                                    <th class="px-6 py-4 text-right">Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                <template x-if="menusLoading">
                                    <tr><td colspan="5" class="px-6 py-8 text-center text-slate-400">Memuat…</td></tr>
                                </template>
                                <template x-if="!menusLoading && menus.length === 0">
                                    <tr><td colspan="5" class="px-6 py-8 text-center text-slate-400">Tidak ada menu yang cocok.</td></tr>
                                </template>
                                <template x-for="menu in menus" :key="menu.id">
                                    <tr class="border-t border-slate-100">
                                        <td class="px-6 py-4 font-semibold" x-text="menu.name"></td>
                                        <td class="px-6 py-4">
                                            <span class="rounded-full bg-slate-100 px-3 py-1 text-xs font-semibold text-slate-600"
                                                x-text="menu.category?.name ?? '-'"></span>
                                        </td>
                                        <td class="px-6 py-4">
                                            <span class="rounded-full bg-orange-50 px-3 py-1 text-xs font-semibold text-orange-600"
                                                x-text="templateLabel(menu.template)"></span>
                                        </td>
                                        <td class="px-6 py-4 font-medium" x-text="'Rp ' + rupiah(menu.price)"></td>
                                        <td class="px-6 py-4 text-right">
                                            <button @click="editMenu(menu)"
                                                class="rounded-lg bg-slate-100 px-4 py-2 text-xs font-semibold text-slate-600 hover:bg-slate-200">Edit</button>
                                            <button @click="destroyMenu(menu)"
                                                class="ml-2 rounded-lg bg-red-100 px-4 py-2 text-xs font-semibold text-red-600 hover:bg-red-200">Hapus</button>
                                        </td>
                                    </tr>
                                </template>
                            </tbody>
                        </table>
                    </div>

                    <div x-show="showMenuForm" x-cloak class="fixed inset-0 z-50 flex items-center justify-center bg-black/50" @click.self="cancelMenuForm()">
                        <form @submit.prevent="saveMenu()" class="mx-4 w-full max-w-md rounded-2xl bg-white p-5 shadow-xl md:p-6">
                            <h2 class="text-lg font-bold" x-text="menuForm.id ? 'Edit Menu' : 'Tambah Menu'"></h2>
                            <div class="mt-4 space-y-4">
                                <div>
                                    <label class="mb-1 block text-sm font-semibold text-slate-600">Nama Menu</label>
                                    <input type="text" x-model="menuForm.name" required placeholder="mis. Nasi Goreng Spesial"
                                        class="w-full rounded-xl border border-slate-300 px-4 py-3 text-sm focus:border-orange-500 focus:outline-none">
                                </div>
                                <div>
                                    <label class="mb-1 block text-sm font-semibold text-slate-600">Kategori</label>
                                    <select x-model.number="menuForm.category_id" required
                                        class="w-full rounded-xl border border-slate-300 px-4 py-3 text-sm focus:border-orange-500 focus:outline-none">
                                        <option value="" disabled>Pilih kategori…</option>
                                        <template x-for="category in categories" :key="category.id">
                                            <option :value="category.id" x-text="category.name"></option>
                                        </template>
                                    </select>
                                </div>
                                <div>
                                    <label class="mb-1 block text-sm font-semibold text-slate-600">Harga (Rp)</label>
                                    <input type="number" x-model.number="menuForm.price" required min="0" step="1" placeholder="mis. 25000"
                                        class="w-full rounded-xl border border-slate-300 px-4 py-3 text-sm focus:border-orange-500 focus:outline-none">
                                </div>
                                <div>
                                    <label class="mb-1 block text-sm font-semibold text-slate-600">Outlet</label>
                                    <select x-model="menuForm.template"
                                        class="w-full rounded-xl border border-slate-300 px-4 py-3 text-sm focus:border-orange-500 focus:outline-none">
                                        <option value="">Tidak ditentukan</option>
                                        <option value="FOODCOURT">Foodcourt</option>
                                        <option value="PASTRY_BAKERY">Pastry &amp; Bakery</option>
                                        <option value="CAFE_1912">Cafe 1912</option>
                                    </select>
                                </div>
                            </div>
                            <div class="mt-6 flex justify-end gap-3">
                                <button type="button" @click="cancelMenuForm()"
                                    class="rounded-xl bg-slate-200 px-5 py-3 text-sm font-semibold text-slate-600">Batal</button>
                                <button type="submit"
                                    class="rounded-xl bg-orange-500 px-5 py-3 text-sm font-semibold text-white hover:bg-orange-600">Simpan</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </section>

        <section x-show="$store.ui.tab === 'history'" x-cloak>
            <div x-data="history" class="flex h-full flex-col">
                <div class="flex flex-wrap items-center justify-between gap-4">
                    <h1 class="text-xl font-bold md:text-2xl">Riwayat Transaksi</h1>
                    <input type="date" x-model="historyDate" @change="load()"
                        class="rounded-xl border border-slate-300 bg-white px-4 py-3 text-sm focus:border-orange-500 focus:outline-none">
                </div>

                <template x-if="error">
                    <div class="mt-4 rounded-xl bg-red-100 px-4 py-3 text-sm font-medium text-red-700" x-text="error"></div>
                </template>

                <div class="mt-4 grid grid-cols-1 gap-4 sm:grid-cols-2">
                    <div class="rounded-2xl bg-white p-5 shadow">
                        <div class="text-sm font-semibold text-slate-500">Jumlah Transaksi</div>
                        <div class="mt-1 text-3xl font-extrabold" x-text="summary.count"></div>
                    </div>
                    <div class="rounded-2xl bg-white p-5 shadow">
                        <div class="text-sm font-semibold text-slate-500">Total Penjualan</div>
                        <div class="mt-1 text-3xl font-extrabold text-orange-600" x-text="'Rp ' + rupiah(summary.total)"></div>
                    </div>
                </div>

                <div class="mt-4 flex-1 overflow-y-auto rounded-2xl bg-white shadow">
                    <table class="w-full text-left text-sm">
                        <thead class="bg-slate-50 text-xs uppercase tracking-wide text-slate-500">
                            <tr>
                                <th class="px-6 py-4">Nomor Nota</th>
                                <th class="px-6 py-4">Waktu</th>
                                <th class="px-6 py-4">Kasir</th>
                                <th class="px-6 py-4">Item</th>
                                <th class="px-6 py-4">Total</th>
                                <th class="px-6 py-4 text-right">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <template x-if="loading">
                                <tr><td colspan="6" class="px-6 py-8 text-center text-slate-400">Memuat…</td></tr>
                            </template>
                            <template x-if="!loading && transactions.length === 0">
                                <tr><td colspan="6" class="px-6 py-8 text-center text-slate-400">Tidak ada transaksi pada tanggal ini.</td></tr>
                            </template>
                            <template x-for="transaction in transactions" :key="transaction.id">
                                <tr class="border-t border-slate-100">
                                    <td class="px-6 py-4 font-semibold" x-text="transaction.receipt_number"></td>
                                    <td class="px-6 py-4" x-text="formatTime(transaction.transaction_time)"></td>
                                    <td class="px-6 py-4" x-text="transaction.cashier_name ?? '-'"></td>
                                    <td class="px-6 py-4" x-text="transaction.items_count + ' item'"></td>
                                    <td class="px-6 py-4 font-bold" x-text="'Rp ' + rupiah(transaction.total)"></td>
                                    <td class="px-6 py-4 text-right">
                                        <button @click="openDetail(transaction.id)"
                                            class="rounded-lg bg-slate-100 px-4 py-2 text-xs font-semibold text-slate-600 hover:bg-slate-200">Detail</button>
                                    </td>
                                </tr>
                            </template>
                        </tbody>
                    </table>
                </div>

                <div x-show="showDetail" x-cloak class="fixed inset-0 z-50 flex items-center justify-center bg-black/50" @click.self="closeDetail()">
                    <div class="mx-4 max-h-[85vh] w-full max-w-md overflow-y-auto rounded-2xl bg-white p-5 shadow-xl md:p-6">
                        <div class="flex items-start justify-between">
                            <div>
                                <h2 class="text-lg font-bold" x-text="detail?.receipt_number"></h2>
                                <p class="text-sm text-slate-500" x-text="detail ? formatTime(detail.transaction_time) : ''"></p>
                            </div>
                            <button @click="closeDetail()" class="rounded-lg bg-slate-100 px-3 py-1.5 text-sm font-bold text-slate-500">✕</button>
                        </div>

                        <div class="mt-4 space-y-2">
                            <template x-for="item in detail?.items ?? []" :key="item.id">
                                <div class="flex justify-between rounded-xl bg-slate-50 px-4 py-3 text-sm">
                                    <div>
                                        <div class="font-semibold" x-text="item.menu_name"></div>
                                        <div class="text-xs text-slate-400" x-text="item.qty + ' x Rp ' + rupiah(item.price)"></div>
                                    </div>
                                    <div class="font-semibold" x-text="'Rp ' + rupiah(item.subtotal)"></div>
                                </div>
                            </template>
                        </div>

                        <div class="mt-4 space-y-1 rounded-xl bg-slate-50 p-4 text-sm">
                            <div class="flex justify-between text-slate-500"><span>Subtotal</span><span x-text="'Rp ' + rupiah(detail?.subtotal)"></span></div>
                            <div class="flex justify-between text-slate-500"><span>Diskon</span><span x-text="'-Rp ' + rupiah(detail?.discount)"></span></div>
                            <div class="flex justify-between text-slate-500"><span>Pajak</span><span x-text="'Rp ' + rupiah(detail?.tax)"></span></div>
                            <div class="flex justify-between border-t border-slate-200 pt-2 font-extrabold"><span>Total</span><span x-text="'Rp ' + rupiah(detail?.total)"></span></div>
                            <div class="flex justify-between text-slate-500"><span>Tunai</span><span x-text="'Rp ' + rupiah(detail?.payment_amount)"></span></div>
                            <div class="flex justify-between text-green-700 font-bold"><span>Kembalian</span><span x-text="'Rp ' + rupiah(detail?.change_amount)"></span></div>
                            <template x-if="detail?.cashier_name">
                                <div class="flex justify-between text-slate-500"><span>Kasir</span><span x-text="detail.cashier_name"></span></div>
                            </template>
                            <template x-if="detail?.order_no">
                                <div class="flex justify-between text-slate-500"><span>No Urut</span><span x-text="detail.order_no"></span></div>
                            </template>
                            <template x-if="detail?.generated_no">
                                <div class="flex justify-between text-slate-500"><span>No</span><span x-text="detail.generated_no"></span></div>
                            </template>
                            <template x-if="detail?.mode">
                                <div class="flex justify-between text-slate-500"><span>Mode</span><span x-text="detail.mode"></span></div>
                            </template>
                            <template x-if="detail?.table_no">
                                <div class="flex justify-between text-slate-500"><span>No Meja</span><span x-text="detail.table_no"></span></div>
                            </template>
                            <template x-if="detail?.entry_time">
                                <div class="flex justify-between text-slate-500"><span>Jam Masuk</span><span x-text="formatTime(detail.entry_time)"></span></div>
                            </template>
                        </div>

                        <button @click="reprint()" :disabled="$store.printing.printing"
                            class="mt-5 w-full rounded-xl bg-orange-500 px-5 py-4 text-base font-bold text-white shadow hover:bg-orange-600 disabled:bg-slate-400"
                            x-text="$store.printing.printing ? 'Mencetak…' : '🖨️ Cetak Ulang Nota'"></button>
                    </div>
                </div>
            </div>
        </section>

        <section x-show="$store.ui.tab === 'settings'" x-cloak>
            <div x-data="printerSettings" class="max-w-xl">
                <h1 class="text-2xl font-bold">Pengaturan Printer</h1>

                <template x-if="!bluetoothSupported">
                    <div class="mt-4 rounded-xl bg-amber-100 px-4 py-3 text-sm font-medium text-amber-800">
                        ⚠️ Browser ini tidak mendukung Web Bluetooth API. Gunakan <strong>Chrome</strong> atau
                        <strong>Edge</strong> (berbasis Chromium) untuk mencetak nota via Bluetooth.
                    </div>
                </template>
                <template x-if="error">
                    <div class="mt-4 rounded-xl bg-red-100 px-4 py-3 text-sm font-medium text-red-700" x-text="error"></div>
                </template>
                <template x-if="notice">
                    <div class="mt-4 rounded-xl bg-green-100 px-4 py-3 text-sm font-medium text-green-700" x-text="notice"></div>
                </template>

                <div class="mt-6 rounded-2xl bg-white p-6 shadow">
                    <div class="flex items-center justify-between rounded-xl bg-slate-50 px-4 py-4">
                        <div>
                            <div class="text-sm font-semibold text-slate-500">Printer Terpasang</div>
                            <div class="mt-1 text-lg font-bold" x-text="pairedPrinterName || 'Belum dipasangkan'"></div>
                        </div>
                        <span class="rounded-full px-3 py-1 text-xs font-bold"
                            :class="pairedPrinterName ? 'bg-green-100 text-green-700' : 'bg-slate-200 text-slate-500'"
                            x-text="pairedPrinterName ? 'Siap' : 'Belum ada'"></span>
                    </div>

                    <button @click="pairPrinter()" :disabled="pairing || !bluetoothSupported"
                        class="mt-4 w-full rounded-xl bg-orange-500 px-5 py-4 text-base font-bold text-white shadow hover:bg-orange-600 disabled:cursor-not-allowed disabled:bg-slate-300"
                        x-text="pairing ? 'Meminta perangkat…' : '🔍 Pair Printer Bluetooth'"></button>

                    <div class="mt-6">
                        <label class="mb-2 block text-sm font-semibold text-slate-600">Lebar Kertas</label>
                        <div class="flex gap-3">
                            <label class="flex flex-1 cursor-pointer items-center justify-center rounded-xl border-2 px-5 py-4 text-sm font-bold"
                                :class="settings.paper_width === '58mm' ? 'border-orange-500 bg-orange-50 text-orange-600' : 'border-slate-200 text-slate-500'">
                                <input type="radio" value="58mm" x-model="settings.paper_width" class="sr-only"> 58mm
                            </label>
                            <label class="flex flex-1 cursor-pointer items-center justify-center rounded-xl border-2 px-5 py-4 text-sm font-bold"
                                :class="settings.paper_width === '80mm' ? 'border-orange-500 bg-orange-50 text-orange-600' : 'border-slate-200 text-slate-500'">
                                <input type="radio" value="80mm" x-model="settings.paper_width" class="sr-only"> 80mm
                            </label>
                        </div>
                    </div>

                    <button @click="save()" :disabled="saving"
                        class="mt-6 w-full rounded-xl bg-slate-900 px-5 py-4 text-base font-bold text-white shadow hover:bg-slate-800 disabled:bg-slate-400"
                        x-text="saving ? 'Menyimpan…' : 'Simpan Pengaturan'"></button>
                </div>

                <p class="mt-4 text-xs text-slate-400">
                    Catatan: fitur cetak Bluetooth memerlukan browser berbasis Chromium dan konteks HTTPS atau localhost.
                    Setelah pairing, browser menyimpan izin perangkat sehingga tidak perlu pairing ulang setiap transaksi.
                </p>
            </div>
        </section>
    </main>

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

<style>
    [x-cloak] { display: none !important; }
</style>
@endsection
