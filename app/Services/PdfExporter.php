<?php

declare(strict_types=1);

namespace App\Services;

/**
 * PdfExporter — Simple mPDF wrapper for exporting report data as PDF.
 *
 * Falls back to HTML output when mPDF is not available.
 */
class PdfExporter
{
    /**
     * Generate and stream a PDF report.
     *
     * @param string $reportType  Human-readable report title.
     * @param array  $data        Report rows.
     * @param array  $filters     Active filters (from, to, days, etc.) for the subtitle.
     * @param array  $columns     Column definitions: [['label' => 'Name', 'key' => 'name', 'align' => 'left'], ...]
     */
    public function exportReport(string $reportType, array $data, array $filters, array $columns = []): void
    {
        $html = $this->buildReportHtml($reportType, $data, $filters, $columns);

        if (class_exists('\\Mpdf\\Mpdf')) {
            $this->streamMpdf($html, $reportType);
        } else {
            $this->streamHtmlFallback($html);
        }
    }

    // -----------------------------------------------------------------------
    // HTML builder
    // -----------------------------------------------------------------------

    private function buildReportHtml(string $title, array $data, array $filters, array $columns): string
    {
        $currency = defined('CURRENCY_SYMBOL') ? CURRENCY_SYMBOL : '₹';

        // Build subtitle from filters.
        $subtitleParts = [];
        if (!empty($filters['from']) && !empty($filters['to'])) {
            $subtitleParts[] = htmlspecialchars($filters['from'], ENT_QUOTES, 'UTF-8')
                             . ' to '
                             . htmlspecialchars($filters['to'], ENT_QUOTES, 'UTF-8');
        }
        if (!empty($filters['days'])) {
            $subtitleParts[] = 'Last ' . (int)$filters['days'] . ' days';
        }
        $subtitle = implode(' | ', $subtitleParts);

        // Auto-detect columns from data keys if not provided.
        if (empty($columns) && !empty($data)) {
            $firstRow = $data[0];
            foreach (array_keys($firstRow) as $key) {
                $columns[] = [
                    'label' => ucwords(str_replace('_', ' ', $key)),
                    'key'   => $key,
                    'align' => is_numeric($firstRow[$key] ?? '') ? 'right' : 'left',
                ];
            }
        }

        // Build header row.
        $headerHtml = '';
        foreach ($columns as $col) {
            $align = $col['align'] ?? 'left';
            $label = htmlspecialchars($col['label'], ENT_QUOTES, 'UTF-8');
            $headerHtml .= "<th style=\"padding:8px;text-align:{$align};background:#4f46e5;color:#fff;font-size:11px;text-transform:uppercase;\">{$label}</th>";
        }

        // Build data rows.
        $rowsHtml = '';
        foreach ($data as $row) {
            $rowsHtml .= '<tr>';
            foreach ($columns as $col) {
                $align = $col['align'] ?? 'left';
                $value = $row[$col['key']] ?? '';

                // Format numeric values.
                if (is_numeric($value)) {
                    $floatVal = (float) $value;
                    // Check if this looks like a currency column.
                    $key = strtolower($col['key']);
                    if (str_contains($key, 'revenue') || str_contains($key, 'cogs') || str_contains($key, 'profit')
                        || str_contains($key, 'total') || str_contains($key, 'value') || str_contains($key, 'price')
                        || str_contains($key, 'balance') || str_contains($key, 'amount') || str_contains($key, 'paid')
                        || str_contains($key, 'tax') || str_contains($key, 'due') || str_contains($key, 'credit')) {
                        $value = $currency . number_format($floatVal, 2);
                    } elseif (str_contains($key, 'pct') || str_contains($key, 'margin')) {
                        $value = number_format($floatVal, 1) . '%';
                    } else {
                        $value = number_format($floatVal, $floatVal == (int)$floatVal ? 0 : 2);
                    }
                }

                $value = htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
                $rowsHtml .= "<td style=\"padding:6px 8px;border-bottom:1px solid #eee;text-align:{$align};font-size:11px;\">{$value}</td>";
            }
            $rowsHtml .= '</tr>';
        }

        $safeTitle = htmlspecialchars($title, ENT_QUOTES, 'UTF-8');
        $generatedAt = date('d M Y H:i');

        return <<<HTML
<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<style>
    body { font-family: Arial, Helvetica, sans-serif; font-size: 12px; color: #333; margin: 0; padding: 20px; }
    .report-box { max-width: 900px; margin: auto; }
    .header { margin-bottom: 16px; }
    .title { font-size: 20px; font-weight: bold; color: #1a1a1a; }
    .subtitle { font-size: 12px; color: #666; margin-top: 4px; }
    table { width: 100%; border-collapse: collapse; }
    .footer { text-align: center; margin-top: 20px; font-size: 10px; color: #999; }
    @media print { body { padding: 0; } }
</style>
</head>
<body>
<div class="report-box">
    <div class="header">
        <div class="title">{$safeTitle}</div>
        <div class="subtitle">{$subtitle} &mdash; Generated: {$generatedAt}</div>
    </div>
    <table>
        <thead><tr>{$headerHtml}</tr></thead>
        <tbody>{$rowsHtml}</tbody>
    </table>
    <div class="footer">Kinara Store Hub</div>
</div>
</body>
</html>
HTML;
    }

    // -----------------------------------------------------------------------
    // Output methods
    // -----------------------------------------------------------------------

    private function streamMpdf(string $html, string $title): void
    {
        $mpdf = new \Mpdf\Mpdf([
            'mode'          => 'utf-8',
            'format'        => 'A4-L',
            'margin_left'   => 10,
            'margin_right'  => 10,
            'margin_top'    => 10,
            'margin_bottom' => 10,
        ]);

        $mpdf->WriteHTML($html);

        $filename = preg_replace('/[^A-Za-z0-9\-]/', '-', $title) . '-' . date('Y-m-d') . '.pdf';

        $mpdf->Output($filename, \Mpdf\Output\Destination::DOWNLOAD);
        exit;
    }

    private function streamHtmlFallback(string $html): void
    {
        header('Content-Type: text/html; charset=utf-8');
        echo $html;
        exit;
    }
}
