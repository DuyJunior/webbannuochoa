document.querySelectorAll('[data-shop-photo-gallery]').forEach(gallery => {
    const image = gallery.querySelector('[data-shop-photo-main]');
    const fullSizeLink = gallery.querySelector('[data-shop-photo-full]');
    const caption = gallery.querySelector('[data-shop-photo-caption]');
    const thumbnails = [...gallery.querySelectorAll('[data-shop-photo]')];

    thumbnails.forEach(thumbnail => thumbnail.addEventListener('click', event => {
        // Preserve browser link behavior for opening photos in another tab.
        if (event.ctrlKey || event.metaKey || event.shiftKey || event.altKey) return;
        event.preventDefault();
        image.src = thumbnail.href;
        image.alt = thumbnail.dataset.photoAlt;
        fullSizeLink.href = thumbnail.href;
        caption.textContent = `${(window.soopiT || (text => text))("Ảnh")} ${thumbnail.dataset.photoPosition} / ${thumbnail.dataset.photoCount}`;
        thumbnails.forEach(link => {
            if (link === thumbnail) link.setAttribute('aria-current', 'true');
            else link.removeAttribute('aria-current');
        });
    }));
});
