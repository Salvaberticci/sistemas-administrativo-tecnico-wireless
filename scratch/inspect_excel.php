<?php
ini_set('memory_limit', '2048M');
require 'vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Reader\IReadFilter;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;

class MyReadFilter implements IReadFilter
{
    public function readCell(string $columnAddress, int $row, string $worksheetName = ''): bool
    {
        return $row <= 5;
    }
}

$inputFileName = 'MARZO.xlsx';

function inspectSheet($fileName, $sheetName) {
    try {
        $reader = IOFactory::createReaderForFile($fileName);
        $reader->setLoadSheetsOnly([$sheetName]);
        $reader->setReadFilter(new MyReadFilter());
        $spreadsheet = $reader->load($fileName);
        $sheet = $spreadsheet->getActiveSheet();
        
        echo "\n--- Sheet: $sheetName ---\n";
        $highestColumn = $sheet->getHighestColumn();
        $highestColumnIndex = Coordinate::columnIndexFromString($highestColumn);
        
        for ($row = 1; $row <= 3; $row++) {
            $rowData = [];
            for ($col = 1; $col <= $highestColumnIndex; $col++) {
                $cellAddress = Coordinate::stringFromColumnIndex($col) . $row;
                $rowData[] = (string)$sheet->getCell($cellAddress)->getValue();
            }
            echo "Row $row: " . implode(" | ", $rowData) . "\n";
        }
    } catch (Exception $e) {
        echo "Error in $sheetName: " . $e->getMessage() . "\n";
    }
}

$sheets = ['DATOS', 'GALANET', 'WIRELESS', 'SUPPLY', 'BDV', 'ZELLE', 'DIVISAS', 'EFECTIVO', 'INSTALACIONES-EQUIPOS', 'MENSUALIDAD'];

foreach ($sheets as $s) {
    inspectSheet($inputFileName, $s);
}
