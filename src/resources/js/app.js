import Alpine from 'alpinejs';
import './pos';
import './generate-nota';

window.Alpine = Alpine;

document.addEventListener('alpine:init', () => {
    Alpine.store('ui', {
        tab: new URLSearchParams(window.location.search).get('tab') || 'cashier',
        sidebarOpen: false,

        goToTab(tab, event) {
            if (window.location.pathname === '/') {
                event.preventDefault();
                this.tab = tab;
                this.sidebarOpen = false;
            }

            // di halaman lain, biarkan <a href="/?tab=..."> navigasi natural
        },

        isTabActive(tab) {
            return window.location.pathname === '/' && this.tab === tab;
        },
    });
});

Alpine.start();
