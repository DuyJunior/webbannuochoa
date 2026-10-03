document.querySelectorAll('[data-gift-bundle]').forEach(form => {
    const selects = [...form.querySelectorAll('select[name="sample_ids[]"]')];
    const refreshChoices = () => selects.forEach(select => {
        const others = selects.filter(other => other !== select).map(other => other.value);
        [...select.options].forEach(option => { option.disabled = !!option.value && others.includes(option.value); });
        select.setCustomValidity(select.value && others.includes(select.value) ? 'Hãy chọn hai mùi mẫu khác nhau.' : '');
    });
    selects.forEach(select => select.addEventListener('change', refreshChoices));
    refreshChoices();
});
