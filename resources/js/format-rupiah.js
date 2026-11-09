export function formatRupiah(value) {
    if (value === null || value === undefined || value === '') {
        return '-';
    }

    return 'Rp ' + Number(value).toLocaleString('id-ID', { maximumFractionDigits: 0 });
}