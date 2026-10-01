// Keep the editorial artwork and the original catalog photograph selectable.
document.addEventListener('click', event => {
    const button = event.target.closest('[data-product-image]');
    if (!button) return;
    const image = document.getElementById('mainProductImage');
    if (!image) return;
    image.src = button.dataset.productImage;
    document.querySelectorAll('[data-product-image]').forEach(item => item.setAttribute('aria-pressed', String(item === button)));
});
