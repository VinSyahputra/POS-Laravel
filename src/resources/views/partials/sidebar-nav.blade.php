<div x-show="$store.ui.sidebarOpen" x-cloak @click="$store.ui.sidebarOpen = false"
    class="fixed inset-0 z-30 bg-black/50 md:hidden"></div>

<aside :class="$store.ui.sidebarOpen ? 'translate-x-0' : '-translate-x-full'"
    class="fixed inset-y-0 left-0 z-40 flex w-28 shrink-0 flex-col gap-3 bg-slate-900 p-3 transition-transform duration-200 md:relative md:translate-x-0">
    <div class="mb-2 flex flex-row-reverse items-center justify-between gap-2 pt-1 md:flex-col md:justify-center md:pt-2">
        <div class="flex items-center gap-2 md:flex-col md:items-center">
            <div class="flex h-12 w-12 shrink-0 items-center justify-center rounded-2xl bg-orange-500 text-2xl">🍽️</div>
            <span class="hidden text-xs font-semibold tracking-wide text-slate-200 md:mt-2 md:block md:text-center">POS FOOD COURT</span>
        </div>
        <button @click="$store.ui.sidebarOpen = false"
            class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-slate-800 text-slate-300 hover:bg-slate-700 hover:text-white md:hidden">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
            </svg>
        </button>
    </div>

    <a href="/?tab=cashier" @click="$store.ui.goToTab('cashier', $event)"
        :class="$store.ui.isTabActive('cashier') ? 'bg-orange-500 text-white' : 'bg-slate-800 text-slate-300 hover:bg-slate-700'"
        class="flex min-h-20 flex-col items-center justify-center gap-1 rounded-xl px-2 py-3 text-sm font-semibold transition">
        <span class="text-2xl">🧾</span> Kasir
    </a>

    <a href="/?tab=menu" @click="$store.ui.goToTab('menu', $event)"
        :class="$store.ui.isTabActive('menu') ? 'bg-orange-500 text-white' : 'bg-slate-800 text-slate-300 hover:bg-slate-700'"
        class="flex min-h-20 flex-col items-center justify-center gap-1 rounded-xl px-2 py-3 text-sm font-semibold transition">
        <span class="text-2xl">🍔</span> Menu
    </a>

    <a href="/?tab=history" @click="$store.ui.goToTab('history', $event)"
        :class="$store.ui.isTabActive('history') ? 'bg-orange-500 text-white' : 'bg-slate-800 text-slate-300 hover:bg-slate-700'"
        class="flex min-h-20 flex-col items-center justify-center gap-1 rounded-xl px-2 py-3 text-sm font-semibold transition">
        <span class="text-2xl">🕘</span> Riwayat
    </a>

    <a href="/?tab=settings" @click="$store.ui.goToTab('settings', $event)"
        :class="$store.ui.isTabActive('settings') ? 'bg-orange-500 text-white' : 'bg-slate-800 text-slate-300 hover:bg-slate-700'"
        class="flex min-h-20 flex-col items-center justify-center gap-1 rounded-xl px-2 py-3 text-sm font-semibold transition">
        <span class="text-2xl">🖨️</span> Printer
    </a>

    <a href="/generate-nota"
        class="flex min-h-20 flex-col items-center justify-center gap-1 rounded-xl px-2 py-3 text-sm font-semibold transition {{ request()->is('generate-nota') ? 'bg-orange-500 text-white' : 'bg-slate-800 text-slate-300 hover:bg-slate-700' }}">
        <span class="text-2xl">🎯</span> Budget
    </a>
</aside>
