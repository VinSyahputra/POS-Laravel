<div class="grid grid-cols-2 gap-3">
    <div class="col-span-2">
        <label class="mb-1 block text-xs font-semibold text-slate-500">Template</label>
        <select x-model="template"
            class="w-full rounded-xl border border-slate-300 bg-white px-3 py-2.5 text-sm font-semibold focus:border-orange-500 focus:outline-none">
            <option value="PASTRY_BAKERY">Pastry &amp; Bakery</option>
            <option value="FOODCOURT">Foodcourt</option>
            <option value="CAFE_1912">Cafe 1912</option>
        </select>
    </div>
    <div>
        <label class="mb-1 block text-xs font-semibold text-slate-500">No Meja</label>
        <input type="text" x-model="tableNo" list="tableNoOptions" placeholder="Pilih / ketik no meja"
            class="w-full rounded-xl border border-slate-300 px-3 py-2.5 text-sm focus:border-orange-500 focus:outline-none">
        <datalist id="tableNoOptions">
            <option value="Quick Service"></option>
            @foreach(range(1, 20) as $i)
                <option value="{{ $i }}"></option>
            @endforeach
        </datalist>
    </div>
    <div>
        <label class="mb-1 block text-xs font-semibold text-slate-500">Mode</label>
        <select x-model="mode"
            class="w-full rounded-xl border border-slate-300 bg-white px-3 py-2.5 text-sm focus:border-orange-500 focus:outline-none">
            <option value="DINE IN">DINE IN</option>
            <option value="TAKEAWAY">TAKEAWAY</option>
        </select>
    </div>
    <div>
        <label class="mb-1 block text-xs font-semibold text-slate-500">Tanggal</label>
        <input type="datetime-local" x-model="orderDate" @change="entryTime = orderDate"
            class="w-full rounded-xl border border-slate-300 px-3 py-2.5 text-sm focus:border-orange-500 focus:outline-none">
    </div>
    <div>
        <label class="mb-1 block text-xs font-semibold text-slate-500">Jam Masuk</label>
        <input type="datetime-local" x-model="entryTime"
            class="w-full rounded-xl border border-slate-300 px-3 py-2.5 text-sm focus:border-orange-500 focus:outline-none">
    </div>
    <div class="col-span-2">
        <label class="mb-1 block text-xs font-semibold text-slate-500">Kasir</label>
        <input type="text" x-model="cashierName" placeholder="Nama kasir"
            class="w-full rounded-xl border border-slate-300 px-3 py-2.5 text-sm focus:border-orange-500 focus:outline-none">
    </div>
    <div class="col-span-2">
        <label class="mb-1 block text-xs font-semibold text-slate-500">No Urut</label>
        <input type="text" x-model="orderNo" @blur="padOrderNo()" placeholder="No. urut"
            class="w-full rounded-xl border border-slate-300 px-3 py-2.5 text-sm focus:border-orange-500 focus:outline-none">
    </div>
    <div class="col-span-2">
        <label class="mb-1 block text-xs font-semibold text-slate-500">No</label>
        <input type="text" :value="generatedNo" readonly
            class="w-full rounded-xl border border-slate-200 bg-slate-50 px-3 py-2.5 text-sm text-slate-500 focus:outline-none">
    </div>
</div>
