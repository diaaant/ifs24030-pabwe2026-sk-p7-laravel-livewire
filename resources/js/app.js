// Definisikan fungsi
function formatRupiah(value) {
    if (value === null || value === undefined || value === '') {
        return '-';
    }

    return 'Rp ' + Number(value).toLocaleString('id-ID', { maximumFractionDigits: 0 });
}

// Ekspos fungsi ke global window
window.formatRupiah = formatRupiah;

console.log('Aplikasi Stock Inventaris berjalan.');