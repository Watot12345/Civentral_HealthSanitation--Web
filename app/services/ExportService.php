<?php
namespace App\Services;

use Dompdf\Dompdf;
use Dompdf\Options;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;

class ExportService
{
    /**
     * Sanitize cell values against CSV/Formula Injection attacks.
     * Prefixes strings starting with =, +, -, @, or tab/CR with a single quote.
     */
    public static function sanitizeCellValue(mixed $val): mixed
    {
        if (!is_string($val)) {
            return $val;
        }

        $trimmed = ltrim($val);
        if ($trimmed !== '' && in_array($trimmed[0], ['=', '+', '-', '@', "\t", "\r"], true)) {
            return "'" . $val;
        }

        return $val;
    }

    /**
     * Generate & stream PDF download via Dompdf
     *
     * @param array $data ['headers' => [...], 'rows' => [...]]
     */
    public static function toPdf(array $data, string $title = 'Report', string $filename = 'report.pdf', int $maxRows = 1000): void
    {
        if (ob_get_level()) {
            ob_end_clean();
        }

        $headers = $data['headers'] ?? [];
        $rows = $data['rows'] ?? [];

        // Row count guard for memory/execution limits (Hostinger/shared hosting)
        $totalRows = count($rows);
        $truncated = false;
        if ($totalRows > $maxRows) {
            $rows = array_slice($rows, 0, $maxRows);
            $truncated = true;
        }

        $html = '<!doctype html><html><head><meta charset="utf-8">';
        $html .= '<style>
            @page { margin: 20px; }
            body { font-family: DejaVu Sans, sans-serif; font-size: 10px; color: #1e293b; margin: 10px; }
            .header { text-align: center; border-bottom: 2px solid #176B87; padding-bottom: 8px; margin-bottom: 12px; }
            .header h1 { margin: 0; font-size: 15px; color: #176B87; text-transform: uppercase; }
            .header h2 { margin: 4px 0 0; font-size: 12px; color: #0F4A5E; font-weight: normal; }
            .header .meta { margin-top: 4px; font-size: 8px; color: #64748b; }
            .warning { background-color: #fef3c7; color: #92400e; padding: 6px; font-size: 9px; border-radius: 4px; margin-bottom: 8px; }
            table { width: 100%; border-collapse: collapse; margin-top: 6px; }
            th { background-color: #176B87; color: #ffffff; padding: 5px 6px; font-size: 9px; text-align: left; border: 1px solid #176B87; }
            td { padding: 5px 6px; border: 1px solid #cbd5e1; font-size: 9px; }
            tr:nth-child(even) { background-color: #f8fafc; }
        </style></head><body>';
        $html .= '<div class="header">';
        $html .= '<h1>Health Sanitation Management Caloocan</h1>';
        $html .= '<h2>' . htmlspecialchars($title, ENT_QUOTES, 'UTF-8') . '</h2>';
        $html .= '<div class="meta">Generated: ' . date('Y-m-d H:i:s') . ' | Total Records: ' . $totalRows . '</div>';
        $html .= '</div>';

        if ($truncated) {
            $html .= '<div class="warning"><strong>Notice:</strong> Report output was capped to the first ' . $maxRows . ' records to prevent rendering timeouts. Please refine your filter criteria for smaller sets.</div>';
        }

        $html .= '<table><thead><tr>';
        foreach ($headers as $h) {
            $html .= '<th>' . htmlspecialchars((string)$h, ENT_QUOTES, 'UTF-8') . '</th>';
        }
        $html .= '</tr></thead><tbody>';
        foreach ($rows as $row) {
            $html .= '<tr>';
            foreach ($row as $col) {
                $html .= '<td>' . htmlspecialchars((string)($col ?? ''), ENT_QUOTES, 'UTF-8') . '</td>';
            }
            $html .= '</tr>';
        }
        $html .= '</tbody></table></body></html>';

        $options = new Options();
        $options->set('isHtml5ParserEnabled', true);
        $options->set('isRemoteEnabled', false);
        $options->set('defaultFont', 'DejaVu Sans');

        $dompdf = new Dompdf($options);
        $dompdf->loadHtml($html);
        $dompdf->setPaper('A4', 'landscape');
        $dompdf->render();

        header('Content-Type: application/pdf');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Cache-Control: private, max-age=0, must-revalidate');
        header('Pragma: public');

        echo $dompdf->output();
        exit;
    }

    /**
     * Generate & stream Excel (.xlsx) download via PhpSpreadsheet
     */
    public static function toExcel(array $data, string $title = 'Report', string $filename = 'report.xlsx'): void
    {
        if (ob_get_level()) {
            ob_end_clean();
        }

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle(substr(preg_replace('/[\\\\\\/?*\\[\\]:]/', '', $title), 0, 31));

        $headers = $data['headers'] ?? [];
        $rows = $data['rows'] ?? [];

        // Title Header
        $sheet->setCellValue('A1', $title);
        $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(13)->getColor()->setRGB('176B87');
        $sheet->setCellValue('A2', 'Generated: ' . date('Y-m-d H:i:s') . ' | Caloocan Health & Sanitation Management');
        $sheet->getStyle('A2')->getFont()->setSize(9)->setItalic(true);

        // Header Row
        $colIndex = 1;
        foreach ($headers as $h) {
            $sheet->setCellValueByColumnAndRow($colIndex, 4, (string)$h);
            $colIndex++;
        }
        $lastCol = $colIndex > 1 ? $colIndex - 1 : 1;
        $sheet->getStyleByColumnAndRow(1, 4, $lastCol, 4)->applyFromArray([
            'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '176B87']],
            'alignment' => ['vertical' => Alignment::VERTICAL_CENTER],
        ]);

        // Data Rows with formula injection prevention
        $rowIndex = 5;
        foreach ($rows as $row) {
            $colIdx = 1;
            foreach ($row as $val) {
                $sanitized = self::sanitizeCellValue($val);
                $sheet->setCellValueByColumnAndRow($colIdx, $rowIndex, $sanitized);
                $colIdx++;
            }
            $rowIndex++;
        }

        foreach (range(1, $lastCol) as $c) {
            $sheet->getColumnDimensionByColumn($c)->setAutoSize(true);
        }

        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Cache-Control: max-age=0');

        $writer = new Xlsx($spreadsheet);
        $writer->save('php://output');
        exit;
    }

    /**
     * Generate & stream CSV download with UTF-8 BOM & formula injection prevention
     */
    public static function toCsv(array $data, string $filename = 'report.csv'): void
    {
        if (ob_get_level()) {
            ob_end_clean();
        }

        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Pragma: no-cache');
        header('Expires: 0');

        $out = fopen('php://output', 'w');
        fprintf($out, chr(0xEF) . chr(0xBB) . chr(0xBF)); // UTF-8 BOM

        $headers = $data['headers'] ?? [];
        if (!empty($headers)) {
            $sanitizedHeaders = array_map([self::class, 'sanitizeCellValue'], $headers);
            fputcsv($out, $sanitizedHeaders);
        }

        foreach ($data['rows'] ?? [] as $row) {
            $sanitizedRow = array_map([self::class, 'sanitizeCellValue'], (array)$row);
            fputcsv($out, $sanitizedRow);
        }

        fclose($out);
        exit;
    }
}
