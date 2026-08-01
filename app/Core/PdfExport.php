<?php

declare(strict_types=1);

namespace App\Core;

/**
 * Dependency-free minimal PDF writer (Helvetica only, Latin-1).
 *
 * Supports pages, text cells, wrapped text, lines, rectangles and
 * simple data tables — enough for professional invoice/report PDFs
 * without external libraries.
 */
class PdfExport
{
    private array $fonts = [
        'helvetica' => ['cw' => '278,278,355,556,556,889,667,191,333,333,389,584,278,333,278,278,556,556,556,556,556,556,556,556,556,556,278,278,584,584,584,556,1015,667,667,722,722,667,611,778,722,278,500,667,556,833,722,778,667,778,722,667,611,722,667,944,667,667,611,278,278,278,469,556,333,556,556,500,556,556,278,556,556,222,222,500,222,833,556,556,556,556,333,500,278,556,500,722,500,500,500,334,260,334,584'],
    ];

    private array $pages = [];
    private string $buffer = '';
    private int $page = -1;

    private float $pageWidth = 595;
    private float $pageHeight = 842;
    private float $mLeft = 40;
    private float $mRight = 40;
    private float $mTop = 40;
    private float $mBottom = 40;

    private float $x = 40;
    private float $y = 40;

    private string $fontFamily = 'helvetica';
    private string $fontStyle = '';
    private float $fontSize = 10;

    private array $textColor = [0, 0, 0];
    private array $drawColor = [0, 0, 0];
    private array $fillColor = [255, 255, 255];
    private float $lineWidth = 0.2;

    private bool $footerEnabled = false;
    private string $footerText = '';
    private string $title = '';

    public function __construct(string $orientation = 'P', string $format = 'A4', string $title = '')
    {
        $this->title = $title;
        if ($format === 'A4') {
            $this->pageWidth = 595;
            $this->pageHeight = 842;
        } elseif ($format === 'LETTER') {
            $this->pageWidth = 612;
            $this->pageHeight = 792;
        } elseif ($format === 'A5') {
            $this->pageWidth = 420;
            $this->pageHeight = 595;
        }
        if (strtoupper($orientation) === 'L') {
            $tmp = $this->pageWidth;
            $this->pageWidth = $this->pageHeight;
            $this->pageHeight = $tmp;
        }
        $this->addPage();
    }

    public function addPage(): void
    {
        if ($this->page >= 0) {
            $this->pages[] = $this->buffer;
        }
        $this->buffer = '';
        $this->page++;
        $this->x = $this->mLeft;
        $this->y = $this->mTop;
    }

    public function setMargins(float $l, float $t, float $r, float $b = 0): void
    {
        $this->mLeft = $l;
        $this->mTop = $t;
        $this->mRight = $r;
        $this->mBottom = $b > 0 ? $b : $t;
        $this->x = $this->mLeft;
        $this->y = $this->mTop;
    }

    public function setFont(string $family = 'helvetica', float $size = 10, string $style = ''): void
    {
        $this->fontFamily = $family;
        $this->fontSize = $size;
        $this->fontStyle = strtoupper($style);
    }

    public function setTextColor(int $r, int $g, int $b): void
    {
        $this->textColor = [$r, $g, $b];
    }

    public function setDrawColor(int $r, int $g, int $b): void
    {
        $this->drawColor = [$r, $g, $b];
    }

    public function setFillColor(int $r, int $g, int $b): void
    {
        $this->fillColor = [$r, $g, $b];
    }

    public function setLineWidth(float $w): void
    {
        $this->lineWidth = $w;
    }

    public function getPageWidth(): float
    {
        return $this->pageWidth;
    }

    public function getPageHeight(): float
    {
        return $this->pageHeight;
    }

    public function getY(): float
    {
        return $this->y;
    }

    public function setY(float $y): void
    {
        $this->y = $y;
    }

    public function getX(): float
    {
        return $this->x;
    }

    public function setX(float $x): void
    {
        $this->x = $x;
    }

    public function ln(float $h = null): void
    {
        $this->y += ($h ?? $this->fontSize * 1.5);
    }

    public function setFooter(string $text, bool $enabled = true): void
    {
        $this->footerText = $text;
        $this->footerEnabled = $enabled;
    }

    /**
     * Ensure the current vertical position fits a given height; else new page.
     */
    private function checkPageBreak(float $h): void
    {
        if ($this->y + $h > $this->pageHeight - $this->mBottom) {
            $this->addPage();
        }
    }

    public function cell(float $w, float $h, string $text, int $border = 0, int $ln = 0, string $align = 'L', bool $fill = false): void
    {
        if ($this->y + $h > $this->pageHeight - $this->mBottom) {
            $this->addPage();
        }

        $x = $this->x;
        $y = $this->y;

        if ($fill) {
            $this->rect($x, $y, $w, $h, 'F');
        }
        if ($border) {
            $this->rect($x, $y, $w, $h, 'D');
        }

        $tWidth = $this->getStringWidth($text);
        $padding = 2;
        switch (strtoupper($align)) {
            case 'R':
                $tx = $x + $w - $padding - $tWidth;
                break;
            case 'C':
                $tx = $x + ($w - $tWidth) / 2;
                break;
            default:
                $tx = $x + $padding;
        }
        $baseline = $y + ($h - $this->fontSize) / 2 + $this->fontSize * 0.72;

        $this->writeText($tx, $baseline, $text);

        if ($ln === 1) {
            $this->x = $this->mLeft;
            $this->y = $y + $h;
        } elseif ($ln === 2) {
            $this->x = $this->mLeft;
            $this->y = $y + $h;
        } else {
            $this->x = $x + $w;
        }
    }

    public function multiCell(float $w, float $h, string $text, int $border = 0, string $align = 'L', bool $fill = false): void
    {
        $words = preg_split('/\s+/', trim($text));
        if (empty($words)) {
            $this->ln($h);
            return;
        }
        $lines = [];
        $current = '';
        foreach ($words as $word) {
            $test = $current === '' ? $word : $current . ' ' . $word;
            if ($this->getStringWidth($test) <= $w - 4) {
                $current = $test;
            } else {
                if ($current !== '') {
                    $lines[] = $current;
                }
                $current = $word;
            }
        }
        if ($current !== '') {
            $lines[] = $current;
        }

        foreach ($lines as $i => $line) {
            $this->cell($w, $h, $line, $i === 0 ? $border : 0, $i === count($lines) - 1 ? 2 : 0, $align, $fill);
            $this->x = $this->x - $w;
            if ($i !== count($lines) - 1) {
                $this->x = $this->x + $w;
                $this->y -= $h;
            }
        }
        $this->y -= $h;
        $this->x = $this->mLeft;
    }

    public function line(float $x1, float $y1, float $x2, float $y2): void
    {
        $this->setStroke();
        $this->buffer .= sprintf(
            "%s %s m %s %s l S\n",
            $this->num($x1),
            $this->num($this->pageHeight - $y1),
            $this->num($x2),
            $this->num($this->pageHeight - $y2)
        );
    }

    public function rect(float $x, float $y, float $w, float $h, string $style = 'D'): void
    {
        $x1 = $this->num($x);
        $y1 = $this->num($this->pageHeight - $y);
        $x2 = $this->num($x + $w);
        $y2 = $this->num($this->pageHeight - ($y + $h));

        if (strpos($style, 'F') !== false) {
            $this->setFill();
            $this->buffer .= sprintf("%s %s %s %s re f\n", $x1, $y2, $this->num($w), $this->num($h));
        }
        if (strpos($style, 'D') !== false) {
            $this->setStroke();
            $this->buffer .= sprintf("%s %s %s %s re S\n", $x1, $y2, $this->num($w), $this->num($h));
        }
    }

    public function writeText(float $x, float $baseline, string $text): void
    {
        $this->setTextStyle();
        $this->buffer .= sprintf(
            "BT /F%s %s Tf 1 0 0 1 %s %s Tm (%s) Tj ET\n",
            $this->fontStyle === 'B' ? 'B' : 'N',
            $this->num($this->fontSize),
            $this->num($x),
            $this->num($this->pageHeight - $baseline),
            $this->escape($text)
        );
    }

    /**
     * Draw a data table with a header row. Auto-wraps, auto page-breaks.
     */
    public function table(array $headers, array $rows, array $widths, float $rowHeight = 16): void
    {
        $cols = count($headers);
        if (count($widths) !== $cols) {
            $widths = array_fill(0, $cols, ($this->pageWidth - $this->mLeft - $this->mRight) / $cols);
        }

        $this->setFont('helvetica', 9, 'B');
        $this->setFillColor(15, 118, 110);
        $this->setTextColor(255, 255, 255);
        $x = $this->mLeft;
        $this->x = $x;
        foreach ($headers as $i => $header) {
            $this->cell($widths[$i], $rowHeight, (string) $header, 0, 0, 'L', true);
            $this->x = $x + array_sum(array_slice($widths, 0, $i + 1));
        }
        $this->setTextColor(30, 41, 59);
        $this->ln($rowHeight);
        $this->x = $this->mLeft;

        $this->setFont('helvetica', 8.5);
        $this->setDrawColor(226, 232, 240);
        $alt = false;
        foreach ($rows as $row) {
            $this->checkPageBreak($rowHeight);
            $alt = !$alt;
            if ($alt) {
                $this->setFillColor(248, 250, 252);
            } else {
                $this->setFillColor(255, 255, 255);
            }
            $x = $this->mLeft;
            $this->x = $x;
            foreach ($headers as $i => $header) {
                $value = (string) ($row[$i] ?? $row[$header] ?? '');
                $this->cell($widths[$i], $rowHeight, $value, 'B', 0, 'L', true);
                $this->x = $x + array_sum(array_slice($widths, 0, $i + 1));
            }
            $this->ln($rowHeight);
            $this->x = $this->mLeft;
        }
        $this->setTextColor(0, 0, 0);
    }

    public function getStringWidth(string $text): float
    {
        $cw = explode(',', $this->fonts['helvetica']['cw']);
        $width = 0;
        $len = strlen($text);
        for ($i = 0; $i < $len; $i++) {
            $char = $text[$i];
            $code = ord($char);
            if ($code >= 32 && $code <= 126) {
                $width += (int) $cw[$code - 32];
            } else {
                $width += 556;
            }
        }
        return $width / 1000 * $this->fontSize;
    }

    private function escape(string $text): string
    {
        $converted = mb_convert_encoding($text, 'ISO-8859-1', 'UTF-8');
        $converted = str_replace('?', '?', $converted);
        // Preserve rupee symbol as 'Rs.' since Latin-1 lacks it
        if (str_contains($text, '₹')) {
            $converted = str_replace('?', 'Rs.', $converted);
        }
        $converted = str_replace(['\\', '(', ')'], ['\\\\', '\(', '\)'], $converted);
        return $converted;
    }

    private function setTextStyle(): void
    {
        $this->buffer .= sprintf(
            "%s %s %s rg\n",
            $this->num($this->textColor[0] / 255),
            $this->num($this->textColor[1] / 255),
            $this->num($this->textColor[2] / 255)
        );
    }

    private function setFill(): void
    {
        $this->buffer .= sprintf(
            "%s %s %s rg\n",
            $this->num($this->fillColor[0] / 255),
            $this->num($this->fillColor[1] / 255),
            $this->num($this->fillColor[2] / 255)
        );
    }

    private function setStroke(): void
    {
        $this->buffer .= sprintf(
            "%s %s %s RG %s w\n",
            $this->num($this->drawColor[0] / 255),
            $this->num($this->drawColor[1] / 255),
            $this->num($this->drawColor[2] / 255),
            $this->num($this->lineWidth)
        );
    }

    private function num(float $value): string
    {
        return rtrim(rtrim(number_format($value, 2, '.', ''), '0'), '.');
    }

    /**
     * Build and return the complete PDF document as a string.
     */
    public function output(): string
    {
        $this->pages[] = $this->buffer;

        $pageCount = count($this->pages);
        $objects = [];

        $objects[] = '<< /Type /Catalog /Pages 2 0 R >>';
        $kids = implode(' ', [3 + 4 * 0 + 3, 0, 'R']);
        $kidsList = [];
        for ($i = 0; $i < $pageCount; $i++) {
            $kidsList[] = (5 + $i * 4) . ' 0 R';
        }
        $objects[] = '<< /Type /Pages /Kids [' . implode(' ', $kidsList) . '] /Count ' . $pageCount . ' >>';

        // Fonts: N = helvetica, B = helvetica-bold
        $objects[] = '<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica /Encoding /WinAnsiEncoding >>';
        $objects[] = '<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica-Bold /Encoding /WinAnsiEncoding >>';

        $pageObjects = [];
        for ($i = 0; $i < $pageCount; $i++) {
            $content = $this->pages[$i];
            $contentObjId = 4 + $i * 4 + 1;
            $pageObjId = 4 + $i * 4 + 2;
            $pageObjects[$i] = [
                'page' => $pageObjId,
                'content' => $contentObjId,
            ];
        }

        $objIndex = 5;
        $output = "%PDF-1.4\n";
        $offsets = [0];
        $numbered = [];

        $catalog = '1 0 obj ' . $objects[0] . ' endobj';
        $pagesObj = '2 0 obj ' . $objects[1] . ' endobj';
        $fontN = '3 0 obj ' . $objects[2] . ' endobj';
        $fontB = '4 0 obj ' . $objects[3] . ' endobj';

        $ordered = [];
        $ordered[1] = $catalog;
        $ordered[2] = $pagesObj;
        $ordered[3] = $fontN;
        $ordered[4] = $fontB;

        $nextId = 5;
        $contentIds = [];
        $pageIds = [];
        foreach ($pageObjects as $i => $po) {
            $contentIds[$i] = $nextId++;
            $pageIds[$i] = $nextId++;
        }

        foreach ($pageObjects as $i => $po) {
            $content = $this->pages[$i];
            if ($this->footerEnabled) {
                $content .= $this->footerContent($i + 1, $pageCount);
            }
            $ordered[$contentIds[$i]] = $contentIds[$i] . ' 0 obj << /Length ' . strlen($content) . ' >> stream' . "\n" . $content . "\nendstream endobj";
            $ordered[$pageIds[$i]] = $pageIds[$i] . ' 0 obj << /Type /Page /Parent 2 0 R /MediaBox [0 0 ' . $this->num($this->pageWidth) . ' ' . $this->num($this->pageHeight) . '] /Resources << /Font << /F1 3 0 R /FB 4 0 R >> >> /Contents ' . $contentIds[$i] . ' 0 R >> endobj';
        }

        ksort($ordered);
        foreach ($ordered as $id => $body) {
            $offsets[$id] = strlen($output);
            $output .= $body . "\n";
        }

        $xrefOffset = strlen($output);
        $output .= "xref\n0 " . (max(array_keys($ordered)) + 1) . "\n";
        $output .= "0000000000 65535 f \n";
        for ($i = 1; $i <= max(array_keys($ordered)); $i++) {
            $offset = $offsets[$i] ?? 0;
            $output .= sprintf("%010d 00000 n \n", $offset);
        }

        $output .= "trailer\n<< /Size " . (max(array_keys($ordered)) + 1) . " /Root 1 0 R >>\nstartxref\n" . $xrefOffset . "\n%%EOF\n";

        return $output;
    }

    private function footerContent(int $pageNumber, int $pageCount): string
    {
        $footer = '';
        if ($this->title !== '') {
            $footer .= sprintf(
                "BT /FN %s Tf 1 0 0 1 %s %s Tm (%s) Tj ET\n",
                $this->num(7),
                $this->num($this->mLeft),
                $this->num($this->mTop / 2),
                $this->escape($this->title)
            );
        }
        $pageLabel = 'Page ' . $pageNumber . ' of ' . $pageCount;
        $width = $this->getStringWidth($pageLabel) * (7 / 8.5);
        $footer .= sprintf(
            "BT /FN 7 Tf 1 0 0 1 %s %s Tm (%s) Tj ET\n",
            $this->num($this->pageWidth - $this->mRight - $width),
            $this->num($this->mTop / 2),
            $this->escape($pageLabel)
        );
        if ($this->footerText !== '') {
            $tw = $this->getStringWidth($this->footerText) * (7 / 8.5);
            $footer .= sprintf(
                "BT /FN 7 Tf 1 0 0 1 %s %s Tm (%s) Tj ET\n",
                $this->num($this->pageWidth / 2 - $tw / 2),
                $this->num($this->pageHeight - $this->mBottom + 8),
                $this->escape($this->footerText)
            );
        }
        return $footer;
    }

    public function download(string $filename): void
    {
        App::getInstance()->response->download($this->output(), $filename, 'application/pdf');
    }
}
