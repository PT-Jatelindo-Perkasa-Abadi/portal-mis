function formatCurrencyCompact(value) {
    if (value === null || value === undefined || isNaN(value)) {
        return 'Rp0';
    }

    value = Number(value);

    const units = [
        { divider: 1e12, suffix: 'T' },   // Triliun
        { divider: 1e9, suffix: 'M' },    // Miliar
        { divider: 1e6, suffix: 'Jt' },   // Juta
        { divider: 1e3, suffix: 'Rb' }    // Ribu
    ];

    for (const unit of units) {
        if (value >= unit.divider) {
            const formatted = (value / unit.divider)
                .toFixed(1)
                .replace(/\.0$/, '');

            return `Rp${formatted}${unit.suffix}`;
        }
    }

    return `Rp${value.toLocaleString('id-ID')}`;
}