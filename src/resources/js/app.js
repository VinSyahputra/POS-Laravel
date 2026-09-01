import Alpine from 'alpinejs';
import './pos';
import './generate-nota';

window.Alpine = Alpine;

document.addEventListener('alpine:init', () => {
    Alpine.store('ui', { tab: 'cashier' });
});

Alpine.start();
