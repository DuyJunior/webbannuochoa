// Native details remain usable without JavaScript. Start compact on small screens.
const footerGroups = [...document.querySelectorAll('[data-footer-group]')];
if (footerGroups.length) {
    const mobile = window.matchMedia('(max-width: 700px)');
    const syncGroups = () => footerGroups.forEach(group => { group.open = !mobile.matches; });
    syncGroups();
    mobile.addEventListener('change', syncGroups);
}
