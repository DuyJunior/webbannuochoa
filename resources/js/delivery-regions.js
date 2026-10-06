// Match only within the selected parent region. Ambiguity must stay unselected.
export function matchDeliveryRegion(rows, nameKey, candidates) {
    const normalize = value => typeof value === 'string' ? value.trim().toLowerCase()
        .normalize('NFD').replace(/[\u0300-\u036f]/g, '').replace(/đ/g, 'd')
        .replace(/^(thanh pho|tinh|quan|huyen|thi xa|phuong|xa|thi tran|tp\.?|q\.?|p\.?)\s+/, '')
        .replace(/\s+/g, ' ') : '';
    if (!Array.isArray(rows) || !Array.isArray(candidates)) return null;
    const names = candidates.map(normalize).filter(Boolean);
    const canonical = rows.filter(row => row && names.includes(normalize(row[nameKey])));
    if (canonical.length) return canonical.length === 1 ? canonical[0] : null;
    const aliases = rows.filter(row => row && Array.isArray(row.NameExtension)
        && row.NameExtension.some(name => names.includes(normalize(name))));
    return aliases.length === 1 ? aliases[0] : null;
}
