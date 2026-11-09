import { describe, expect, it } from 'vitest';
import { formatRupiah } from '../../resources/js/format-rupiah.js';

describe('formatRupiah', () => {
    it('mengembalikan tanda minus untuk nilai kosong', () => {
        expect(formatRupiah(null)).toBe('-');
        expect(formatRupiah(undefined)).toBe('-');
        expect(formatRupiah('')).toBe('-');
    });

    it('memformat angka sebagai rupiah', () => {
        expect(formatRupiah(1500000)).toBe('Rp 1.500.000');
        expect(formatRupiah('200000')).toBe('Rp 200.000');
    });
});