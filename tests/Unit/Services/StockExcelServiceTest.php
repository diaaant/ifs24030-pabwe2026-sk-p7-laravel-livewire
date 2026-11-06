<?php

namespace Tests\Unit\Services;

use App\Features\Stock\Services\StockExcelService;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Tests\TestCase;

class StockExcelServiceTest extends TestCase
{
    private function writeTemp(string $content): string
    {
        $path = tempnam(sys_get_temp_dir(), 'tst_');
        file_put_contents($path, $content);

        return $path;
    }

    private function makeXlsx(array $rows): string
    {
        $spreadsheet = new Spreadsheet;
        $spreadsheet->getActiveSheet()->fromArray($rows, null, 'A1');

        $path = tempnam(sys_get_temp_dir(), 'xl_');
        (new Xlsx($spreadsheet))->save($path);

        return $path;
    }

    public function test_export_produces_valid_xlsx_with_headers_and_rows(): void
    {
        $user = $this->makeMember();
        $this->makeStock($user, ['name' => 'Pulpen', 'sku' => 'P-1', 'quantity' => 10, 'unit' => 'box', 'price' => 2500, 'min_stock' => 3]);
        $this->makeStock($user, ['name' => 'Tanpa Harga', 'sku' => null, 'price' => null, 'quantity' => 0, 'status' => 'out_of_stock']);

        $content = (new StockExcelService)->export($user->stocks()->orderBy('id')->get());

        $this->assertStringStartsWith('PK', $content);

        $path = $this->writeTemp($content);
        $rows = IOFactory::load($path)->getActiveSheet()->toArray();
        unlink($path);

        $this->assertSame(StockExcelService::HEADERS, $rows[0]);
        $this->assertSame('Pulpen', $rows[1][0]);
        $this->assertSame('P-1', $rows[1][1]);
        $this->assertEquals(10, $rows[1][2]);
        $this->assertSame('box', $rows[1][3]);
        $this->assertSame('Tanpa Harga', $rows[2][0]);
        $this->assertNull($rows[2][1]);
        $this->assertNull($rows[2][4]);
        $this->assertSame('out_of_stock', $rows[2][6]);
        $this->assertCount(3, $rows);
    }

    public function test_export_empty_collection_only_has_header(): void
    {
        $path = $this->writeTemp((new StockExcelService)->export([]));
        $rows = IOFactory::load($path)->getActiveSheet()->toArray();
        unlink($path);

        $this->assertCount(1, $rows);
    }

    public function test_import_reads_valid_rows_and_skips_empty_names(): void
    {
        $path = $this->makeXlsx([
            StockExcelService::HEADERS,
            ['Pulpen', 'PLP-1', 10, 'box', 2500.5, 3],
            [null, 'X', 1, 'pcs', 1, 1],
            ['Buku', '', 'abc', null, 'gratis', 'x'],
            ['Spidol', 1001, -5, 'pcs', 100, -2],
        ]);

        $items = (new StockExcelService)->import($path);
        unlink($path);

        $this->assertCount(3, $items);

        $this->assertSame('Pulpen', $items[0]['name']);
        $this->assertSame('PLP-1', $items[0]['sku']);
        $this->assertSame(10, $items[0]['quantity']);
        $this->assertSame('box', $items[0]['unit']);
        $this->assertSame(2500.5, $items[0]['price']);
        $this->assertSame(3, $items[0]['min_stock']);

        $this->assertSame('Buku', $items[1]['name']);
        $this->assertSame(0, $items[1]['quantity']);
        $this->assertSame('pcs', $items[1]['unit']);
        $this->assertNull($items[1]['price']);
        $this->assertSame(5, $items[1]['min_stock']);

        $this->assertSame('Spidol', $items[2]['name']);
        $this->assertSame(0, $items[2]['quantity']);
        $this->assertSame(0, $items[2]['min_stock']);
    }

    public function test_import_header_only_returns_empty_array(): void
    {
        $path = $this->makeXlsx([StockExcelService::HEADERS]);

        $this->assertSame([], (new StockExcelService)->import($path));
        unlink($path);
    }
}
