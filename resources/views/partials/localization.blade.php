<style>
.language-switcher{display:inline-flex;align-items:center;gap:8px;margin:0;max-width:100%;flex-shrink:0;font:12px/1.5 'Be Vietnam Pro',system-ui,sans-serif;color:inherit}
.language-switcher label{position:relative;display:flex;align-items:center;margin:0;flex-shrink:0}
.language-switcher label::after{content:'';position:absolute;right:14px;top:50%;width:6px;height:6px;border-right:1.5px solid currentColor;border-bottom:1.5px solid currentColor;transform:translateY(-75%) rotate(45deg);pointer-events:none}
.language-label{position:absolute;width:1px;height:1px;overflow:hidden;clip-path:inset(50%)}
.language-switcher select{appearance:none;box-sizing:border-box;font:inherit;color:inherit;background:transparent;border:1px solid #c8b8c1;border-radius:7px;padding:9px 34px 9px 12px;min-height:40px;width:146px;min-width:146px;cursor:pointer}
.language-switcher option{color:#392333;background:#fff}
.language-switcher select:focus-visible,.language-apply:focus-visible{outline:2px solid #9a5f7f;outline-offset:3px}
.language-apply{font:inherit;color:inherit;background:transparent;border:1px solid #c8b8c1;border-radius:5px;padding:7px;cursor:pointer}
html.has-language-js .language-apply{display:none}
.language-mobile{display:none}.language-footer{margin-top:20px}.language-login{margin-bottom:24px}
.ht-footer .language-switcher,.ht-footer .language-switcher label{color:#f7edf2}
.ht-footer .language-switcher select{color:#f7edf2!important}
@media(max-width:1250px){.sn-bar{column-gap:14px}}
@media(min-width:901px) and (max-width:1400px){.soopi-header .sn-bar{grid-template-columns:minmax(120px,1fr) auto auto}}
@media(max-width:900px){.sn-tools .language-desktop{display:none}.language-mobile{display:inline-flex;margin:8px 20px 18px}}
@media print{.language-switcher{display:none!important}}
</style>
<script>
document.documentElement.classList.add('has-language-js');
window.soopiLocale = @json(app()->getLocale());
window.soopiTranslations = @json(app()->getLocale() === 'en' ? json_decode(file_get_contents(lang_path('en-ui.json')), true) : (object) []);
window.soopiT = function (message, replacements = {}) {
    let result = Object.prototype.hasOwnProperty.call(window.soopiTranslations, message) ? window.soopiTranslations[message] : message;
    for (const [key, value] of Object.entries(replacements)) result = result.replaceAll(':'+key, String(value));
    return result;
};
document.addEventListener('change', function (event) {
    const form = event.target.closest('[data-language-switcher]');
    if (!form || event.target.name !== 'locale') return;
    form.elements.return_to.value = location.pathname + location.search + location.hash;
    form.requestSubmit();
});
</script>
