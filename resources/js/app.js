import './bootstrap';
import '../css/store-information.css';
import '../css/gift-bundle.css';
import './gift-bundle.js';
import './store-header.js';
import './store-navigation.js';
import './store-motion.js';
import './store-interiors.js';
import './product-quick-view.js';
import './scent-experience.js';
import './atelier-typewriter.js';
import './wishlist.js';
import './journal-video-rail.js';
import './email-verification.js';

document.addEventListener('click', (event) => {
    const button = event.target.closest('[data-quantity-minus], [data-quantity-plus]');

    if (!button) return;

    const picker = button.closest('[data-quantity-picker]');
    const input = picker?.querySelector('input[type="number"]');

    if (!input) return;

    const min = Number(input.min || 1);
    const max = Number(input.max || Number.MAX_SAFE_INTEGER);
    const current = Number(input.value || min);
    const next = button.hasAttribute('data-quantity-plus') ? current + 1 : current - 1;

    input.value = String(Math.min(max, Math.max(min, next)));
});

document.addEventListener('click', (event) => {
    const categoryMenu = document.querySelector('.store-category-menu');

    if (categoryMenu?.open && !categoryMenu.contains(event.target)) {
        categoryMenu.removeAttribute('open');
    }
});

document.addEventListener('keydown', (event) => {
    if (event.key === 'Escape') {
        document.querySelector('.store-category-menu[open]')?.removeAttribute('open');
    }
});

// Native details menus work with mouse, touch and keyboard.
document.addEventListener('click', (event) => {
    document.querySelectorAll('.ht-account[open], .ht-category-menu[open]').forEach(menu => {
        if (!menu.contains(event.target)) menu.open = false;
    });
});
document.addEventListener('keydown', (event) => {
    if (event.key !== 'Escape') return;
    document.querySelectorAll('.ht-account[open], .ht-category-menu[open]').forEach(menu => {
        menu.open = false;
        menu.querySelector('summary')?.focus();
    });
});
