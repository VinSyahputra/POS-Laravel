import Alpine from 'alpinejs';
import { api, formatRupiah } from './api';
import { isBluetoothSupported, requestPrinter, restorePairedPrinter, getStoredPrinterName, writeBytes } from './bluetooth';
import { buildReceiptBytes, buildReceiptText } from './escpos';

document.addEventListener('alpine:init', () => {
    Alpine.store('printing', {
        printing: false,
        error: null,
        notice: null,
        showPreview: false,
        previewText: '',
        paperWidth: '80mm',
        transaction: null,

        receiptOptions() {
            return {
                outletName: window.POS_OUTLET ?? 'POS Food Court',
                paperWidth: this.paperWidth,
            };
        },

        async printReceipt(transaction) {
            if (!transaction || this.printing) {
                return;
            }
            this.transaction = transaction;
            this.printing = true;
            this.error = null;
            this.notice = null;
            try {
                const settings = await api('/printer-settings').catch(() => null);
                this.paperWidth = settings?.data?.paper_width ?? '80mm';
                const bytes = buildReceiptBytes(transaction, this.receiptOptions());
                await writeBytes(bytes);
                this.notice = 'Nota berhasil dicetak.';
            } catch (e) {
                this.error = e.message;
                this.previewText = buildReceiptText(transaction, this.receiptOptions());
                this.showPreview = true;
            } finally {
                this.printing = false;
            }
        },

        async reprint() {
            this.showPreview = false;
            await this.printReceipt(this.transaction);
        },

        reset() {
            this.error = null;
            this.notice = null;
            this.showPreview = false;
            this.previewText = '';
            this.transaction = null;
        },
    });

    Alpine.data('menuManager', () => ({
        tab: 'menu',
        subTab: 'categories',
        categories: [],
        menus: [],
        menusLoading: false,
        loading: false,
        error: null,
        notice: null,
        showCategoryForm: false,
        categoryForm: { id: null, name: '' },
        menuFilterCategoryId: '',
        showMenuForm: false,
        menuForm: { id: null, name: '', price: '', category_id: '' },

        async init() {
            await Promise.all([this.loadCategories(), this.loadMenus()]);
        },

        switchTab(tab) {
            this.tab = tab;
        },

        switchSubTab(subTab) {
            this.subTab = subTab;
        },

        async loadCategories() {
            this.loading = true;
            this.error = null;
            try {
                const data = await api('/categories');
                this.categories = data.data;
            } catch (e) {
                this.error = e.message;
            } finally {
                this.loading = false;
            }
        },

        editCategory(category) {
            this.categoryForm = { id: category.id, name: category.name };
            this.showCategoryForm = true;
        },

        async saveCategory() {
            this.error = null;
            this.notice = null;
            try {
                if (this.categoryForm.id) {
                    await api(`/categories/${this.categoryForm.id}`, {
                        method: 'PUT',
                        body: JSON.stringify({ name: this.categoryForm.name }),
                    });
                    this.notice = 'Kategori berhasil diperbarui.';
                } else {
                    await api('/categories', {
                        method: 'POST',
                        body: JSON.stringify({ name: this.categoryForm.name }),
                    });
                    this.notice = 'Kategori berhasil ditambahkan.';
                }
                this.cancelCategoryForm();
                await this.loadCategories();
            } catch (e) {
                this.error = e.errors ? Object.values(e.errors).flat().join(' ') : e.message;
            }
        },

        cancelCategoryForm() {
            this.showCategoryForm = false;
            this.categoryForm = { id: null, name: '' };
        },

        async destroyCategory(category) {
            if (!confirm(`Hapus kategori "${category.name}"?`)) {
                return;
            }
            this.error = null;
            this.notice = null;
            try {
                await api(`/categories/${category.id}`, { method: 'DELETE' });
                this.notice = 'Kategori berhasil dihapus.';
                await this.loadCategories();
            } catch (e) {
                this.error = e.message;
            }
        },

        rupiah(value) {
            return formatRupiah(value);
        },

        async loadMenus() {
            this.menusLoading = true;
            this.error = null;
            try {
                const query = this.menuFilterCategoryId ? `?category_id=${this.menuFilterCategoryId}` : '';
                const data = await api(`/menus${query}`);
                this.menus = data.data;
            } catch (e) {
                this.error = e.message;
            } finally {
                this.menusLoading = false;
            }
        },

        async filterMenus() {
            await this.loadMenus();
        },

        editMenu(menu) {
            this.menuForm = {
                id: menu.id,
                name: menu.name,
                price: menu.price,
                category_id: menu.category_id,
            };
            this.showMenuForm = true;
        },

        async saveMenu() {
            this.error = null;
            this.notice = null;
            const payload = {
                name: this.menuForm.name,
                price: Number(this.menuForm.price),
                category_id: Number(this.menuForm.category_id),
            };
            try {
                if (this.menuForm.id) {
                    await api(`/menus/${this.menuForm.id}`, {
                        method: 'PUT',
                        body: JSON.stringify(payload),
                    });
                    this.notice = 'Menu berhasil diperbarui.';
                } else {
                    await api('/menus', {
                        method: 'POST',
                        body: JSON.stringify(payload),
                    });
                    this.notice = 'Menu berhasil ditambahkan.';
                }
                this.cancelMenuForm();
                await Promise.all([this.loadMenus(), this.loadCategories()]);
            } catch (e) {
                this.error = e.errors ? Object.values(e.errors).flat().join(' ') : e.message;
            }
        },

        cancelMenuForm() {
            this.showMenuForm = false;
            this.menuForm = { id: null, name: '', price: '', category_id: '' };
        },

        async destroyMenu(menu) {
            if (!confirm(`Hapus menu "${menu.name}"?`)) {
                return;
            }
            this.error = null;
            this.notice = null;
            try {
                await api(`/menus/${menu.id}`, { method: 'DELETE' });
                this.notice = 'Menu berhasil dihapus.';
                await Promise.all([this.loadMenus(), this.loadCategories()]);
            } catch (e) {
                this.error = e.message;
            }
        },
    }));

    Alpine.data('cashier', () => ({
        categories: [],
        menus: [],
        filterCategoryId: '',
        cart: [],
        loading: false,
        error: null,

        async init() {
            this.resetOrderFields();
            await this.refresh();

            this.$watch('$store.ui.tab', (tab) => {
                if (tab === 'cashier') {
                    this.refresh();
                }
            });
        },

        async refresh() {
            this.loading = true;
            this.error = null;
            try {
                const [categoryResponse, menuResponse] = await Promise.all([
                    api('/categories'),
                    api('/menus'),
                ]);
                this.categories = categoryResponse.data;
                this.menus = menuResponse.data;
            } catch (e) {
                this.error = e.message;
            } finally {
                this.loading = false;
            }
        },

        get filteredMenus() {
            if (!this.filterCategoryId) {
                return this.menus;
            }

            return this.menus.filter((menu) => menu.category_id === this.filterCategoryId);
        },

        rupiah(value) {
            return formatRupiah(value);
        },

        addToCart(menu) {
            const existing = this.cart.find((item) => item.menu_id === menu.id);

            if (existing) {
                existing.qty += 1;
                return;
            }

            this.cart.push({ menu_id: menu.id, name: menu.name, price: menu.price, qty: 1 });
            this.cartExpanded = true;
        },

        increase(menuId) {
            const item = this.cart.find((entry) => entry.menu_id === menuId);

            if (item) {
                item.qty += 1;
            }
        },

        decrease(menuId) {
            const item = this.cart.find((entry) => entry.menu_id === menuId);

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
            this.cart = this.cart.filter((entry) => entry.menu_id !== menuId);
        },

        moveUp(menuId) {
            const index = this.cart.findIndex((entry) => entry.menu_id === menuId);

            if (index <= 0) {
                return;
            }

            [this.cart[index - 1], this.cart[index]] = [this.cart[index], this.cart[index - 1]];
        },

        moveDown(menuId) {
            const index = this.cart.findIndex((entry) => entry.menu_id === menuId);

            if (index === -1 || index >= this.cart.length - 1) {
                return;
            }

            [this.cart[index + 1], this.cart[index]] = [this.cart[index], this.cart[index + 1]];
        },

        clearCart() {
            if (this.cart.length > 0 && !confirm('Kosongkan keranjang?')) {
                return;
            }

            this.cart = [];
        },

        itemSubtotal(item) {
            return item.qty * item.price;
        },

        get cartCount() {
            return this.cart.reduce((sum, item) => sum + item.qty, 0);
        },

        get cartSubtotal() {
            return this.cart.reduce((sum, item) => sum + item.qty * item.price, 0);
        },

        discount: 0,
        tax: 0,
        paymentAmount: '',
        cashierName: '',
        showPayment: false,
        cartExpanded: false,
        submitting: false,
        lastTransaction: null,
        orderNo: '',
        tableNo: '',
        mode: 'DINE IN',
        orderDate: '',
        entryTime: '',
        template: 'FOODCOURT',

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

        resetOrderFields() {
            const now = new Date();

            this.orderNo = '';
            this.tableNo = '';
            this.mode = 'DINE IN';
            this.template = 'FOODCOURT';
            this.orderDate = this.toLocalInputValue(now);
            this.entryTime = this.toLocalInputValue(now);
        },

        get total() {
            return Math.max(0, this.cartSubtotal - (Number(this.discount) || 0)) + (Number(this.tax) || 0);
        },

        get changeAmount() {
            const paid = Number(this.paymentAmount) || 0;

            return Math.max(0, paid - this.total);
        },

        get canSubmit() {
            return this.cart.length > 0 && (Number(this.paymentAmount) || 0) >= this.total && !this.submitting;
        },

        quickCash(amount) {
            this.paymentAmount = amount;
        },

        openPayment() {
            this.discount = 0;
            this.tax = 0;
            this.paymentAmount = '';
            this.showPayment = true;
            this.cartExpanded = true;
        },

        async submitTransaction() {
            if (!this.canSubmit) {
                return;
            }
            this.submitting = true;
            this.error = null;
            try {
                const payload = {
                    items: this.cart.map((item) => ({ menu_id: item.menu_id, qty: item.qty })),
                    discount: Number(this.discount) || 0,
                    tax: Number(this.tax) || 0,
                    payment_amount: Number(this.paymentAmount) || 0,
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
                this.cart = [];
                this.showPayment = false;
                this.paymentAmount = '';
                this.discount = 0;
                this.tax = 0;
                this.resetOrderFields();
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

    Alpine.data('printerSettings', () => ({
        settings: { printer_name: null, paper_width: '80mm' },
        bluetoothSupported: isBluetoothSupported(),
        pairedPrinterName: '',
        pairing: false,
        saving: false,
        error: null,
        notice: null,

        async init() {
            await this.load();

            if (this.bluetoothSupported) {
                await this.restoreSession();
            }
        },

        async load() {
            try {
                const data = await api('/printer-settings');
                this.settings = data.data;
            } catch (e) {
                this.error = e.message;
            }
        },

        async restoreSession() {
            const restored = await restorePairedPrinter();

            if (restored?.name) {
                this.pairedPrinterName = restored.name;
            } else {
                this.pairedPrinterName = this.settings.printer_name ?? getStoredPrinterName();
            }
        },

        async pairPrinter() {
            this.error = null;
            this.notice = null;
            this.pairing = true;
            try {
                const device = await requestPrinter();
                this.pairedPrinterName = device.name ?? '(tanpa nama)';
                this.settings.printer_name = this.pairedPrinterName;
                await this.save(true);
                this.notice = `Printer "${this.pairedPrinterName}" berhasil dipasangkan.`;
            } catch (e) {
                this.error = e.message;
            } finally {
                this.pairing = false;
            }
        },

        async save(silent = false) {
            this.error = null;
            if (!silent) {
                this.notice = null;
            }
            this.saving = true;
            try {
                const data = await api('/printer-settings', {
                    method: 'PUT',
                    body: JSON.stringify({
                        printer_name: this.settings.printer_name,
                        paper_width: this.settings.paper_width,
                    }),
                });
                this.settings = data.data;
                if (!silent) {
                    this.notice = 'Pengaturan printer tersimpan.';
                }
            } catch (e) {
                this.error = e.errors ? Object.values(e.errors).flat().join(' ') : e.message;
            } finally {
                this.saving = false;
            }
        },
    }));

    Alpine.data('history', () => ({
        historyDate: new Date().toISOString().slice(0, 10),
        transactions: [],
        summary: { count: 0, total: 0 },
        loading: false,
        error: null,
        detail: null,
        showDetail: false,
        loadingDetail: false,

        async init() {
            await this.load();

            this.$watch('$store.ui.tab', (tab) => {
                if (tab === 'history') {
                    this.load();
                }
            });
        },

        async load() {
            this.loading = true;
            this.error = null;
            try {
                const data = await api(`/transactions?date=${this.historyDate}`);
                this.transactions = data.data;
                this.summary = data.summary;
            } catch (e) {
                this.error = e.message;
            } finally {
                this.loading = false;
            }
        },

        async openDetail(id) {
            this.loadingDetail = true;
            this.error = null;
            try {
                const data = await api(`/transactions/${id}`);
                this.detail = data.data;
                this.showDetail = true;
            } catch (e) {
                this.error = e.message;
            } finally {
                this.loadingDetail = false;
            }
        },

        closeDetail() {
            this.showDetail = false;
            this.detail = null;
        },

        async reprint() {
            if (this.detail) {
                await this.$store.printing.printReceipt(this.detail);
            }
        },

        formatTime(isoString) {
            return new Date(isoString).toLocaleTimeString('id-ID', { hour: '2-digit', minute: '2-digit' });
        },

        rupiah(value) {
            return formatRupiah(value);
        },
    }));
});
