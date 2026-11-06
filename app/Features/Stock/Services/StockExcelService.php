<?php

namespace App\Features\Stock\Services;

use App\Features\Stock\Models\Stock;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

class StockExcelService
{
    public const HEADERS = ['Name', 'SKU', 'Quantity', 'Unit', 'Price', 'Min Stock', 'Status', 'Created At'];

    /**
     * Export koleksi stok menjadi konten file XLSX (binary string).
     *
     * @param  iterable<Stock>  $stocks
     */
    public function export(iterable $stocks): string
    {
        $spreadsheet = new Spreadsheet;
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Stocks');
        $sheet->fromArray(self::HEADERS, null, 'A1');

        $row = 2;
        foreach ($stocks as $stock) {
            $sheet->fromArray([
                $stock->name,
                $stock->sku,
                $stock->quantity,
                $stock->unit,
                $stock->price,
                $stock->min_stock,
                $stock->status->value,
                $stock->created_at?->format('Y-m-d H:i:s'),
            ], null, 'A'.$row);
            $row++;
        }

        $path = tempnam(sys_get_temp_dir(), 'stock_');
        (new Xlsx($spreadsheet))->save($path);
        $content = (string) file_get_contents($path);
        unlink($path);

        return $content;
    }

    /**
     * Baca file XLSX dan kembalikan baris data stok yang valid.
     * Baris pertama dianggap header; baris tanpa nama diabaikan.
     *
     * @return array<int, array<string, mixed>>
     */
    public function import(string $path): array
    {
        $rows = IOFactory::load($path)->getActiveSheet()->toArray(null, true, false, false);
        array_shift($rows);

        $items = [];
        foreach ($rows as $row) {
            $name = trim((string) ($row[0] ?? ''));
            if ($name === '') {
                continue;
            }

            $items[] = [
                'name' => $name,
                'sku' => $row[1] ?? null,
                'quantity' => is_numeric($row[2] ?? null) ? max(0, (int) $row[2]) : 0,
                'unit' => $row[3] ?? 'pcs',
                'price' => is_numeric($row[4] ?? null) ? (float) $row[4] : null,
                'min_stock' => is_numeric($row[5] ?? null) ? max(0, (int) $row[5]) : 5,
                'description' => null,
            ];
        }

        return $items;
    }
}
