function formatNumber(value) {
    if (value === null || value === undefined || isNaN(value)) {
        return '0';
    }

    return Number(value).toLocaleString('id-ID');
}