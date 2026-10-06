import '../css/order-folio.css';

document.addEventListener('submit', (event) => {
    const form = event.target.closest('[data-order-cancel]');
    if (form && !window.confirm(form.dataset.confirm)) event.preventDefault();
});
