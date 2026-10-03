<style>
.language-switcher{display:inline-flex;align-items:center;gap:8px;margin:0;max-width:100%;font:12px/1.5 'Be Vietnam Pro',system-ui,sans-serif;color:inherit}.language-switcher label{display:flex;align-items:center;gap:8px;margin:0}.language-label{position:absolute;width:1px;height:1px;overflow:hidden;clip-path:inset(50%)}.language-switcher select{font:inherit;color:inherit;background:transparent;border:1px solid #c8b8c1;border-radius:7px;padding:9px 22px 9px 9px;min-height:40px;max-width:150px;cursor:pointer}.language-switcher option{color:#392333;background:#fff}.language-switcher select:focus-visible,.language-apply:focus-visible{outline:2px solid #9a5f7f;outline-offset:3px}.language-apply{font:inherit;color:inherit;background:transparent;border:1px solid #c8b8c1;border-radius:5px;padding:7px;cursor:pointer}html.has-language-js .language-apply{display:none}.language-mobile{display:none}.language-footer{margin-top:20px}.language-login{margin-bottom:24px}.sn-tools .language-switcher select{max-width:125px}.topbar-right .language-switcher{flex-shrink:0}@media(max-width:1250px){.sn-tools .language-switcher select{width:66px}.sn-bar{column-gap:14px}}@media(max-width:900px){.sn-tools .language-desktop{display:none}.language-mobile{display:inline-flex;margin:8px 20px 18px}.topbar-right .language-switcher select{width:66px}}@media print{.language-switcher{display:none!important}}
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
