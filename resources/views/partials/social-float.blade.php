{{-- FLOATING SOCIAL CONTACT BUTTONS --}}
<div class="ht-social-float" aria-label="Liên hệ nhanh">
    <div class="ht-social-float-inner">
        <a href="https://zalo.me/0123456789" target="_blank" rel="noopener"
           class="ht-float-btn ht-float-zalo"
           aria-label="Chat Zalo với Soopi">
            <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false">
                <path d="M21 11.5a8.5 8.5 0 0 1-8.5 8.5 9.5 9.5 0 0 1-4-.9L3 21l1.9-5.5a9.5 9.5 0 0 1-.9-4A8.5 8.5 0 0 1 12.5 3h.5a8.5 8.5 0 0 1 8 8v.5Z"/>
                <path d="M8 10h9M8 14h6"/>
            </svg>
            <span class="ht-float-label">Zalo</span>
        </a>
        <a href="https://m.me/hathu.perfume" target="_blank" rel="noopener"
           class="ht-float-btn ht-float-messenger"
           aria-label="Nhắn tin Facebook Messenger">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="white">
                <path d="M12 0C5.374 0 0 4.974 0 11.111c0 3.498 1.744 6.614 4.469 8.652V24l4.088-2.242c1.092.3 2.246.464 3.443.464 6.626 0 12-4.974 12-11.111S18.626 0 12 0zm1.191 14.963l-3.055-3.26-5.963 3.26L10.732 8l3.131 3.26L19.752 8l-6.561 6.963z"/>
            </svg>
            <span class="ht-float-label">Messenger</span>
        </a>
        <a href="https://instagram.com/hathu.perfume" target="_blank" rel="noopener"
           class="ht-float-btn ht-float-instagram"
           aria-label="Instagram Soopi">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="white">
                <path d="M12 2.163c3.204 0 3.584.012 4.85.07 3.252.148 4.771 1.691 4.919 4.919.058 1.265.069 1.645.069 4.849 0 3.205-.012 3.584-.069 4.849-.149 3.225-1.664 4.771-4.919 4.919-1.266.058-1.644.07-4.85.07-3.204 0-3.584-.012-4.849-.07-3.26-.149-4.771-1.699-4.919-4.92-.058-1.265-.07-1.644-.07-4.849 0-3.204.013-3.583.07-4.849.149-3.227 1.664-4.771 4.919-4.919 1.266-.057 1.645-.069 4.849-.069zM12 0C8.741 0 8.333.014 7.053.072 2.695.272.273 2.69.073 7.052.014 8.333 0 8.741 0 12c0 3.259.014 3.668.072 4.948.2 4.358 2.618 6.78 6.98 6.98C8.333 23.986 8.741 24 12 24c3.259 0 3.668-.014 4.948-.072 4.354-.2 6.782-2.618 6.979-6.98.059-1.28.073-1.689.073-4.948 0-3.259-.014-3.667-.072-4.947-.196-4.354-2.617-6.78-6.979-6.98C15.668.014 15.259 0 12 0zm0 5.838a6.162 6.162 0 100 12.324 6.162 6.162 0 000-12.324zM12 16a4 4 0 110-8 4 4 0 010 8zm6.406-11.845a1.44 1.44 0 100 2.881 1.44 1.44 0 000-2.881z"/>
            </svg>
            <span class="ht-float-label">Instagram</span>
        </a>
    </div>
</div>

<style>
/* ── FLOATING SOCIAL BUTTONS ── */
.ht-social-float {
    position: fixed;
    right: 24px;
    bottom: 100px;
    z-index: 9000;
    display: flex;
    flex-direction: column;
    gap: 10px;
}
.ht-social-float-inner {
    display: flex;
    flex-direction: column;
    align-items: flex-end;
    gap: 10px;
    animation: ht-float-in .5s cubic-bezier(0.34,1.56,0.64,1) both;
    animation-delay: 1.2s;
}
@keyframes ht-float-in {
    from { opacity: 0; transform: translateX(30px); }
    to   { opacity: 1; transform: translateX(0); }
}
.ht-float-btn {
    display: flex;
    flex-direction: row-reverse;
    align-items: center;
    gap: 0;
    width: 48px;
    height: 48px;
    border-radius: 50%;
    overflow: hidden;
    text-decoration: none;
    box-shadow: 0 4px 16px rgba(0,0,0,.18);
    transition: width .28s cubic-bezier(0.34,1.56,0.64,1),
                border-radius .28s,
                box-shadow .18s,
                transform .18s;
    position: relative;
    white-space: nowrap;
}
.ht-float-btn:hover,
.ht-float-btn:focus-visible {
    width: 148px;
    border-radius: 24px;
    box-shadow: 0 8px 24px rgba(0,0,0,.22);
    transform: scale(1.04);
}
.ht-float-btn svg {
    flex-shrink: 0;
    width: 48px;
    height: 48px;
    padding: 13px;
    box-sizing: border-box;
}
.ht-float-label {
    font-size: 13px;
    font-weight: 700;
    color: #fff;
    opacity: 0;
    max-width: 0;
    overflow: hidden;
    transition: opacity .2s .05s, max-width .28s;
    padding-left: 0;
}
.ht-float-btn:hover .ht-float-label,
.ht-float-btn:focus-visible .ht-float-label {
    opacity: 1;
    max-width: 90px;
    padding-left: 14px;
}
.ht-float-zalo      { background: #0068FF; }
.ht-float-messenger { background: linear-gradient(135deg, #00B2FF, #006AFF, #7B28FF); }
.ht-float-instagram { background: linear-gradient(45deg,#f09433,#e6683c,#dc2743,#cc2366,#bc1888); }

/* Shift on mobile to avoid overlap with chat button */
@media (max-width: 640px) {
    .ht-social-float {
        right: 16px;
        bottom: 90px;
    }
    .ht-float-btn { width: 42px; height: 42px; }
    .ht-float-btn svg { width: 42px; height: 42px; padding: 11px; }
}
</style>
