import Alpine from 'alpinejs';
import { api, formatRupiah } from './api';

document.addEventListener('alpine:init', () => {
    Alpine.data('budgetGenerator', () => ({
        template: 'FOODCOURT',
        tableNo: 'Quick Service',
        mode: 'DINE IN',
        orderDate: '',
        entryTime: '',
        cashierName: '',
        orderNo: '',

        targetBudget: '',
        maxQtyPerItem: 10,
        items: [],
        generating: false,
        generateError: null,
        generateFailed: false,

        menus: [],
        menuSearch: '',
        loading: false,
        error: null,

        paymentAmount: '',
        submitting: false,
        lastTransaction: null,

        async init() {
            const now = new Date();
            this.orderDate = this.toLocalInputValue(now);
            this.entryTime = this.toLocalInputValue(now);
            await this.loadMenus();
            this.$watch('template', () => this.loadMenus());
        },

        async loadMenus() {
            this.loading = true;
            this.error = null;
            try {
                const data = await api(`/menus?template=${this.template}`);
                this.menus = data.data;
            } catch (e) {
                this.error = e.message;
            } finally {
                this.loading = false;
            }
        },

        get filteredMenus() {
            const keyword = this.menuSearch.trim().toLowerCase();

            return this.menus.filter((menu) => !keyword || menu.name.toLowerCase().includes(keyword));
        },

        rupiah(value) {
            return formatRupiah(value);
        },

        toLocalInputValue(date) {
            const pad = (value) => String(value).padStart(2, '0');

            return `${date.getFullYear()}-${pad(date.getMonth() + 1)}-${pad(date.getDate())}T${pad(date.getHours())}:${pad(date.getMinutes())}`;
        },

        toIsoOrNull(value) {
            if (!value) {
                return null;
            }

            const date = new Date(value);

            return Number.isNaN(date.getTime()) ? null : date.toISOString();
        },

        padOrderNo() {
            if (this.orderNo && /^\d+$/.test(this.orderNo)) {
                this.orderNo = this.orderNo.padStart(4, '0');
            }
        },

        get generatedNo() {
            const codes = { FOODCOURT: 'UMB0101', PASTRY_BAKERY: 'UMB0201', CAFE_1912: 'UMB0301' };
            const code = codes[this.template] ?? '';
            const date = this.orderDate ? new Date(this.orderDate) : new Date();
            const pad = (value) => String(value).padStart(2, '0');
            const ymd = Number.isNaN(date.getTime())
                ? ''
                : `${date.getFullYear()}${pad(date.getMonth() + 1)}${pad(date.getDate())}`;
            const orderNo = /^\d+$/.test(this.orderNo) ? this.orderNo.padStart(4, '0') : this.orderNo;

            return `${code}${ymd}${orderNo}`;
        },

        async generate() {
            this.generating = true;
            this.generateError = null;
            this.generateFailed = false;
            this.items = [];

            try {
                const response = await api('/menu-combos/generate', {
                    method: 'POST',
                    body: JSON.stringify({
                        target: Number(this.targetBudget) || 0,
                        template: this.template,
                        max_qty_per_item: Number(this.maxQtyPerItem) || 10,
                    }),
                });

                this.items = response.data.items.map((item) => ({
                    menu_id: item.menu_id,
                    name: item.name,
                    price: item.price,
                    qty: item.qty,
                }));
            } catch (e) {
                this.generateFailed = true;
                this.generateError = e.message;
            } finally {
                this.generating = false;
            }
        },

        addToCart(menu) {
            const existing = this.items.find((item) => item.menu_id === menu.id);

            if (existing) {
                existing.qty += 1;
                return;
            }

            this.items.push({ menu_id: menu.id, name: menu.name, price: menu.price, qty: 1 });
        },

        increase(menuId) {
            const item = this.items.find((entry) => entry.menu_id === menuId);

            if (item) {
                item.qty += 1;
            }
        },

        decrease(menuId) {
            const item = this.items.find((entry) => entry.menu_id === menuId);

            if (!item) {
                return;
            }

            if (item.qty <= 1) {
                this.removeItem(menuId);

                return;
            }

            item.qty -= 1;
        },

        removeItem(menuId) {
            this.items = this.items.filter((entry) => entry.menu_id !== menuId);
        },

        itemSubtotal(item) {
            return item.qty * item.price;
        },

        get subtotal() {
            return this.items.reduce((sum, item) => sum + item.qty * item.price, 0);
        },

        get difference() {
            return (Number(this.targetBudget) || 0) - this.subtotal;
        },

        get changeAmount() {
            const paid = Number(this.paymentAmount) || 0;

            return Math.max(0, paid - this.subtotal);
        },

        get canSubmit() {
            return this.items.length > 0 && (Number(this.paymentAmount) || 0) >= this.subtotal && !this.submitting;
        },

        resetAll() {
            this.items = [];
            this.targetBudget = '';
            this.paymentAmount = '';
            this.generateFailed = false;
            this.generateError = null;
            this.orderNo = '';
            this.tableNo = 'Quick Service';
            this.mode = 'DINE IN';
            this.cashierName = '';

            const now = new Date();
            this.orderDate = this.toLocalInputValue(now);
            this.entryTime = this.toLocalInputValue(now);
        },

        async submitTransaction() {
            if (!this.canSubmit) {
                return;
            }

            this.padOrderNo();
            this.submitting = true;
            this.error = null;

            try {
                const payload = {
                    items: this.items.map((item) => ({ menu_id: item.menu_id, qty: item.qty })),
                    payment_amount: Number(this.paymentAmount) || 0,
                    payment_method: 'CASH',
                    cashier_name: this.cashierName || null,
                    order_no: this.orderNo || null,
                    template: this.template || null,
                    table_no: this.tableNo || null,
                    mode: this.mode,
                    transaction_time: this.toIsoOrNull(this.orderDate),
                    entry_time: this.toIsoOrNull(this.entryTime),
                };
                const response = await api('/transactions', {
                    method: 'POST',
                    body: JSON.stringify(payload),
                });
                this.lastTransaction = response.data;
                this.resetAll();
                this.$nextTick(() => this.$store.printing.printReceipt(this.lastTransaction));
            } catch (e) {
                this.error = e.errors ? Object.values(e.errors).flat().join(' ') : e.message;
            } finally {
                this.submitting = false;
            }
        },

        closeReceipt() {
            this.lastTransaction = null;
            this.$store.printing.reset();
        },
    }));
});
