{{-- ── BOUTIQUE VIDEO PLAYER MODAL ── --}}
<div id="htVideoModal" class="ht-video-modal" aria-hidden="true" style="display:none;">
    <div class="ht-video-modal-backdrop" id="htVideoBackdrop"></div>
    <div class="ht-video-modal-dialog" role="dialog" aria-modal="true" aria-labelledby="htVideoModalTitle">
        <button type="button" class="ht-video-modal-close" id="htVideoCloseBtn" aria-label="Đóng video">&times;</button>
        
        <div class="ht-video-modal-content">
            {{-- Video Player Box --}}
            <div class="ht-video-player-container">
                <iframe id="htVideoIframe" title="Video giới thiệu nước hoa" frameborder="0" allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture" allowfullscreen></iframe>
                <video id="htVideoHtml5" controls style="display:none; width:100%; height:100%; border-radius:12px; background:#000;"></video>
            </div>

            {{-- Video Details & Product Link --}}
            <div class="ht-video-details">
                <div class="ht-video-meta">
                    <span class="ht-video-tag">@include('partials.icon', ['name' => 'video', 'size' => '1em']) Video Review</span>
                    <span class="ht-video-views-badge">@include('partials.icon', ['name' => 'flame', 'size' => '1em']) <span id="htVideoViews">12.4K lượt xem</span></span>
                </div>
                <h3 id="htVideoModalTitle" class="ht-video-title">Tiêu đề video</h3>
                <p id="htVideoModalDesc" class="ht-video-desc">Mô tả video review...</p>

                {{-- Linked Product Card --}}
                <div id="htVideoPerfumeWrap" class="ht-video-perfume-card" style="display:none;">
                    <div class="perfume-thumb">
                        <img id="htVideoPerfumeImg" src="" alt="">
                    </div>
                    <div class="perfume-meta">
                        <span class="perfume-brand" id="htVideoPerfumeBrand"></span>
                        <h4 class="perfume-name" id="htVideoPerfumeName"></h4>
                        <div class="perfume-price" id="htVideoPerfumePrice"></div>
                    </div>
                    <a id="htVideoPerfumeLink" href="#" class="ht-video-buy-btn">
                        Xem & Mua Ngay @include('partials.icon', ['name' => 'bag', 'size' => '1em'])
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.ht-video-modal {
    position: fixed;
    inset: 0;
    z-index: 99999;
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 16px;
    opacity: 0;
    visibility: hidden;
    transition: opacity .25s ease, visibility .25s ease;
}
.ht-video-modal.is-active {
    opacity: 1;
    visibility: visible;
}
.ht-video-modal-backdrop {
    position: absolute;
    inset: 0;
    background: rgba(15, 10, 18, 0.85);
    backdrop-filter: blur(8px);
}
.ht-video-modal-dialog {
    position: relative;
    z-index: 2;
    background: #ffffff;
    border-radius: 20px;
    width: 100%;
    max-width: 820px;
    max-height: calc(100vh - 32px);
    overflow-y: auto;
    box-shadow: 0 25px 60px rgba(0,0,0,0.4);
    transform: scale(0.92);
    transition: transform .25s cubic-bezier(0.16, 1, 0.3, 1);
    border: 1px solid rgba(232, 114, 138, 0.2);
}
.ht-video-modal.is-active .ht-video-modal-dialog {
    transform: scale(1);
}
.ht-video-modal-close {
    position: absolute;
    top: 12px;
    right: 14px;
    z-index: 10;
    width: 38px;
    height: 38px;
    border-radius: 50%;
    background: rgba(0,0,0,0.6);
    color: #ffffff;
    border: 0;
    font-size: 24px;
    line-height: 1;
    display: flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    transition: all .2s;
}
.ht-video-modal-close:hover {
    background: #db2777;
    transform: rotate(90deg);
}
.ht-video-modal-content {
    display: flex;
    flex-direction: column;
}
.ht-video-player-container {
    position: relative;
    width: 100%;
    aspect-ratio: 16 / 9;
    background: #000000;
    border-top-left-radius: 20px;
    border-top-right-radius: 20px;
    overflow: hidden;
}
.ht-video-player-container iframe {
    width: 100%;
    height: 100%;
    border: 0;
}
.ht-video-details {
    padding: 24px;
}
.ht-video-meta {
    display: flex;
    align-items: center;
    gap: 10px;
    margin-bottom: 8px;
}
.ht-video-tag {
    background: #fdf2f8;
    color: #db2777;
    font-size: 11px;
    font-weight: 700;
    text-transform: uppercase;
    padding: 3px 10px;
    border-radius: 12px;
    letter-spacing: 0.5px;
}
.ht-video-views-badge {
    font-size: 12px;
    color: #64748b;
    font-weight: 600;
}
.ht-video-title {
    font-family: 'Playfair Display', Georgia, serif;
    font-size: 1.35rem;
    font-weight: 700;
    color: #1e1b18;
    margin: 0 0 10px;
    line-height: 1.35;
}
.ht-video-desc {
    font-size: 13.5px;
    color: #475569;
    line-height: 1.6;
    margin: 0 0 18px;
}
.ht-video-perfume-card {
    display: flex;
    align-items: center;
    gap: 16px;
    padding: 14px 18px;
    background: linear-gradient(135deg, #fff7f9, #fdf2f8);
    border: 1px solid #fce7f3;
    border-radius: 14px;
    flex-wrap: wrap;
}
.ht-video-perfume-card .perfume-thumb {
    width: 54px;
    height: 54px;
    border-radius: 10px;
    background: #ffffff;
    display: flex;
    align-items: center;
    justify-content: center;
    overflow: hidden;
    flex-shrink: 0;
    box-shadow: 0 2px 8px rgba(0,0,0,0.06);
}
.ht-video-perfume-card .perfume-thumb img {
    max-height: 100%;
    max-width: 100%;
    object-fit: contain;
}
.ht-video-perfume-card .perfume-meta {
    flex: 1;
    min-width: 160px;
}
.ht-video-perfume-card .perfume-brand {
    font-size: 11px;
    text-transform: uppercase;
    letter-spacing: 1px;
    color: #9d174d;
    font-weight: 700;
    display: block;
}
.ht-video-perfume-card .perfume-name {
    font-size: 14px;
    font-weight: 700;
    color: #1e293b;
    margin: 2px 0;
}
.ht-video-perfume-card .perfume-price {
    font-size: 13.5px;
    font-weight: 700;
    color: #db2777;
}
.ht-video-buy-btn {
    background: linear-gradient(135deg, #e8728a, #db2777);
    color: #ffffff !important;
    font-size: 13px;
    font-weight: 700;
    padding: 9px 18px;
    border-radius: 10px;
    text-decoration: none !important;
    display: inline-flex;
    align-items: center;
    box-shadow: 0 4px 14px rgba(219, 39, 119, 0.3);
    transition: transform .2s, box-shadow .2s;
    white-space: nowrap;
}
.ht-video-buy-btn:hover {
    transform: translateY(-2px);
    box-shadow: 0 6px 18px rgba(219, 39, 119, 0.4);
}
@media (max-width: 640px) {
    .ht-video-details { padding: 18px; }
    .ht-video-title { font-size: 1.15rem; }
    .ht-video-perfume-card { flex-direction: column; align-items: stretch; text-align: center; }
    .ht-video-perfume-card .perfume-meta { text-align: center; }
    .ht-video-buy-btn { justify-content: center; }
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const modal = document.getElementById('htVideoModal');
    if (!modal) return;

    const backdrop = document.getElementById('htVideoBackdrop');
    const closeBtn = document.getElementById('htVideoCloseBtn');
    const iframe = document.getElementById('htVideoIframe');
    const html5Video = document.getElementById('htVideoHtml5');
    const titleEl = document.getElementById('htVideoModalTitle');
    const descEl = document.getElementById('htVideoModalDesc');
    const viewsEl = document.getElementById('htVideoViews');

    const perfumeWrap = document.getElementById('htVideoPerfumeWrap');
    const perfumeImg = document.getElementById('htVideoPerfumeImg');
    const perfumeBrand = document.getElementById('htVideoPerfumeBrand');
    const perfumeName = document.getElementById('htVideoPerfumeName');
    const perfumePrice = document.getElementById('htVideoPerfumePrice');
    const perfumeLink = document.getElementById('htVideoPerfumeLink');
    let returnFocus = null;
    let closeTimer = null;
    let previousOverflow = '';

    window.openBoutiqueVideoModal = function (data) {
        if (!data) return;
        clearTimeout(closeTimer);
        if (!modal.classList.contains('is-active')) {
            returnFocus = document.activeElement;
            previousOverflow = document.body.style.overflow;
        }

        titleEl.textContent = data.title || 'Video Trải Nghiệm Nước Hoa';
        descEl.textContent = data.desc || '';
        viewsEl.textContent = (data.views || '0') + ' lượt xem';

        // Xử lý player (YouTube vs Direct Video)
        const embedUrl = data.embed || data.url || '';
        if (/\.(mp4|webm|ogg)(?:[?#]|$)/i.test(embedUrl)) {
            iframe.style.display = 'none';
            iframe.removeAttribute('src');
            html5Video.style.display = 'block';
            html5Video.src = embedUrl;
            html5Video.play().catch(() => { /* Native controls remain available when autoplay is blocked. */ });
        } else {
            html5Video.style.display = 'none';
            html5Video.pause();
            html5Video.removeAttribute('src');
            html5Video.load();
            iframe.style.display = 'block';
            // Đảm bảo có autoplay
            let src = embedUrl;
            if (src.includes('youtube.com') && !src.includes('autoplay=1')) {
                src += (src.includes('?') ? '&' : '?') + 'autoplay=1&rel=0';
            }
            iframe.src = src;
        }

        // Xử lý gắn sản phẩm
        if (data.perfumeName && data.perfumeUrl) {
            perfumeWrap.style.display = 'flex';
            perfumeName.textContent = data.perfumeName;
            perfumeBrand.textContent = data.perfumeBrand || 'NƯỚC HOA CHÍNH HÃNG';
            perfumePrice.textContent = data.perfumePrice || '';
            perfumeLink.href = data.perfumeUrl;
            if (data.perfumeImg) {
                perfumeImg.src = data.perfumeImg;
                perfumeImg.style.display = 'block';
            } else {
                perfumeImg.style.display = 'none';
            }
        } else {
            perfumeWrap.style.display = 'none';
        }

        modal.style.display = 'flex';
        requestAnimationFrame(() => {
            modal.classList.add('is-active');
            modal.setAttribute('aria-hidden', 'false');
            closeBtn.focus();
        });
        document.body.style.overflow = 'hidden';
    };

    window.closeBoutiqueVideoModal = function () {
        modal.classList.remove('is-active');
        modal.setAttribute('aria-hidden', 'true');
        iframe.removeAttribute('src');
        html5Video.pause();
        html5Video.removeAttribute('src');
        html5Video.load();
        document.body.style.overflow = previousOverflow;
        returnFocus?.focus();
        closeTimer = setTimeout(() => {
            modal.style.display = 'none';
        }, 260);
    };

    if (backdrop) backdrop.addEventListener('click', window.closeBoutiqueVideoModal);
    if (closeBtn) closeBtn.addEventListener('click', window.closeBoutiqueVideoModal);

    document.addEventListener('keydown', function (e) {
        const trigger = e.target.closest('.js-open-video');
        if (trigger && (e.key === 'Enter' || e.key === ' ')) {
            e.preventDefault();
            trigger.click();
        }
        if (e.key === 'Escape' && modal.classList.contains('is-active')) {
            window.closeBoutiqueVideoModal();
        }
        if (e.key === 'Tab' && modal.classList.contains('is-active')) {
            const focusable = [...modal.querySelectorAll('button, a[href], iframe, video[controls]')].filter(el => el.getClientRects().length);
            const first = focusable[0], last = focusable[focusable.length - 1];
            if (e.shiftKey && document.activeElement === first) { e.preventDefault(); last?.focus(); }
            else if (!e.shiftKey && document.activeElement === last) { e.preventDefault(); first?.focus(); }
        }
    });

    // Bắt sự kiện click vào các thẻ video có class .js-open-video
    document.addEventListener('click', function (e) {
        const trigger = e.target.closest('.js-open-video');
        if (trigger) {
            e.preventDefault();
            const data = {
                title: trigger.getAttribute('data-title'),
                embed: trigger.getAttribute('data-embed'),
                desc: trigger.getAttribute('data-desc'),
                views: trigger.getAttribute('data-views'),
                perfumeName: trigger.getAttribute('data-perfume-name'),
                perfumeBrand: trigger.getAttribute('data-perfume-brand'),
                perfumePrice: trigger.getAttribute('data-perfume-price'),
                perfumeUrl: trigger.getAttribute('data-perfume-url'),
                perfumeImg: trigger.getAttribute('data-perfume-img'),
            };
            window.openBoutiqueVideoModal(data);
        }
    });
});
</script>
