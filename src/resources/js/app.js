import Alpine from 'alpinejs';
import './pos';
import './generate-nota';

window.Alpine = Alpine;

document.addEventListener('alpine:init', () => {
    Alpine.store('ui', {
        tab: new URLSearchParams(window.location.search).get('tab') || 'cashier',
        sidebarOpen: false,

        goToTab(tab) {
            if (window.location.pathname === '/') {
                this.tab = tab;
                this.sidebarOpen = false;
                return;
            }

            window.location.href = '/?tab=' + tab;
        },

        isTabActive(tab) {
            return window.location.pathname === '/' && this.tab === tab;
        },
    });
});

Alpine.start();
