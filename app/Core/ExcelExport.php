<?php

declare(strict_types=1);

namespace App\Core;

/**
 * Dependency-free Excel 2003 XML (SpreadsheetML) exporter.
 * Outputs a .xls file readable by Excel, LibreOffice and Google Sheets.
 */
class ExcelExport
{
    private array $headers = [];
    private array $rows = [];
    private string $sheetName = 'Sheet1';
    private array $colWidths = [];

    public function __construct(string $sheetName = 'Sheet1')
    {
        $this->sheetName = preg_replace('/[\\\\\/\?\*\[\]:]/', '', $sheetName) ?: 'Sheet1';
    }

    public function setHeaders(array $headers): self
    {
        $this->headers = $headers;
        return $this;
    }

    public function addRow(array $row): self
    {
        $this->rows[] = $row;
        return $this;
    }

    public function addRows(array $rows): self
    {
        foreach ($rows as $row) {
            $this->addRow($row);
        }
        return $this;
    }

    public function setColWidths(array $widths): self
    {
        $this->colWidths = $widths;
        return $this;
    }

    public function build(): string
    {
        $headerCells = '';
        foreach ($this->headers as $i => $header) {
            $width = $this->colWidths[$i] ?? 120;
            $headerCells .= $this->cell(e($header), $i, 'header');
        }

        $bodyRows = '';
        $rowIndex = 1;
        foreach ($this->rows as $row) {
            $cells = '';
            foreach (array_values($row) as $i => $value) {
                if ($value === null || $value === '') {
                    $cells .= '<Cell><Data ss:Type="String"></Data></Cell>';
                } elseif (is_numeric($value) && !str_starts_with((string) $value, '0')) {
                    $cells .= '<Cell ss:StyleID="num"><Data ss:Type="Number">' . (float) $value . '</Data></Cell>';
                } else {
                    $cells .= '<Cell><Data ss:Type="String">' . e($value) . '</Data></Cell>';
                }
            }
            $bodyRows .= '<Row ss:AutoFitHeight="0">' . $cells . '</Row>';
            $rowIndex++;
        }

        $styleSheet = '<Styles>'
            . '<Style ss:ID="Default" ss:Name="Normal"><Alignment ss:Vertical="Center"/></Style>'
            . '<Style ss:ID="header"><Font ss:Bold="1" ss:Color="#FFFFFF" ss:FontName="Inter"/><Interior ss:Color="#0F766E" ss:Pattern="Solid"/><Alignment ss:Vertical="Center"/></Style>'
            . '<Style ss:ID="num"><NumberFormat ss:Format="#,##0.00"/></Style>'
            . '</Styles>';

        $cols = '';
        foreach ($this->headers as $i => $header) {
            $width = $this->colWidths[$i] ?? 120;
            $cols .= '<Column ss:AutoFitWidth="0" ss:Width="' . $width . '"/>';
        }

        return '<?xml version="1.0" encoding="UTF-8"?>'
            . '<?mso-application progid="Excel.Sheet"?>'
            . '<Workbook xmlns="urn:schemas-microsoft-com:office:spreadsheet"'
            . ' xmlns:o="urn:schemas-microsoft-com:office:office"'
            . ' xmlns:x="urn:schemas-microsoft-com:office:excel"'
            . ' xmlns:ss="urn:schemas-microsoft-com:office:spreadsheet"'
            . ' xmlns:html="http://www.w3.org/TR/REC-html40">'
            . $styleSheet
            . '<Worksheet ss:Name="' . e($this->sheetName) . '">'
            . '<Table>' . $cols
            . '<Row ss:AutoFitHeight="0" ss:Height="22">' . $headerCells . '</Row>'
            . $bodyRows
            . '</Table>'
            . '<WorksheetOptions xmlns="urn:schemas-microsoft-com:office:excel"><ProtectObjects>False</ProtectObjects><ProtectScenarios>False</ProtectScenarios></WorksheetOptions>'
            . '</Worksheet></Workbook>';
    }

    private function cell(string $content, int $col, string $style): string
    {
        $width = $this->colWidths[$col] ?? 120;
        return '<Cell ss:StyleID="' . $style . '" ss:MergeAcross="0">'
            . '<Data ss:Type="String">' . $content . '</Data></Cell>';
    }

    public function download(string $filename): void
    {
        App::getInstance()->response->download(
            $filename,
            $this->build(),
            'application/vnd.ms-excel'
        );
    }
}
