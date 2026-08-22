import Alpine from 'alpinejs';
import './pos';

window.Alpine = Alpine;

document.addEventListener('alpine:init', () => {
    Alpine.store('ui', { tab: 'cashier' });
});

Alpine.start();
