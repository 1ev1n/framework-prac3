<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Shared\Date as PhpSpreadsheetDate;
use Symfony\Component\HttpFoundation\StreamedResponse;

class CsvController extends Controller
{
    public function index()
    {
        $files = $this->getCsvFiles();
        return view('csv.index', ['files' => $files]);
    }

    public function view($filename)
    {
        $filepath = '/data/csv/' . basename($filename);
        if (!file_exists($filepath)) {
            return redirect('/csv')->with('error', 'Файл не найден');
        }

        $data = $this->parseCsv($filepath);
        return view('csv.view', [
            'filename' => $filename,
            'headers' => $data['headers'] ?? [],
            'rows' => $data['rows'] ?? [],
        ]);
    }

    public function exportXlsx($filename)
    {
        try {
            $filepath = '/data/csv/' . basename($filename);
            if (!file_exists($filepath) || !is_readable($filepath)) {
                return redirect('/csv')->with('error', 'Файл не найден или недоступен');
            }

            $data = $this->parseCsv($filepath);
            
            if (empty($data['headers']) || empty($data['rows'])) {
                return redirect('/csv')->with('error', 'CSV файл пуст или имеет неверный формат');
            }
            
            $spreadsheet = new Spreadsheet();
            $sheet = $spreadsheet->getActiveSheet();
            $sheet->setTitle('Telemetry Data');

            // Headers
            $headers = $data['headers'] ?? [];
            $col = 'A';
            foreach ($headers as $header) {
                $sheet->setCellValue($col . '1', $header);
                $sheet->getStyle($col . '1')->applyFromArray([
                    'font' => ['bold' => true],
                    'fill' => [
                        'fillType' => Fill::FILL_SOLID,
                        'startColor' => ['rgb' => 'E0E0E0']
                    ],
                    'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER]
                ]);
                $col++;
            }

            // Data
            $row = 2;
            foreach ($data['rows'] ?? [] as $dataRow) {
                $col = 'A';
                $colIndex = 0;
                foreach ($dataRow as $value) {
                    if ($colIndex >= count($headers)) break; // Защита от лишних столбцов
                    
                    $value = trim($value ?? '');
                    
                    // Detect type and format accordingly
                    if (preg_match('/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}Z$/', $value)) {
                        // Timestamp - конвертируем в формат Excel
                        try {
                            $dateTime = \DateTime::createFromFormat('Y-m-d\TH:i:s\Z', $value, new \DateTimeZone('UTC'));
                            if ($dateTime) {
                                $excelDate = PhpSpreadsheetDate::PHPToExcel($dateTime->getTimestamp());
                                $sheet->setCellValue($col . $row, $excelDate);
                                $sheet->getStyle($col . $row)->getNumberFormat()->setFormatCode('yyyy-mm-dd hh:mm:ss');
                            } else {
                                $sheet->setCellValue($col . $row, $value);
                            }
                        } catch (\Exception $e) {
                            $sheet->setCellValue($col . $row, $value);
                        }
                    } elseif (in_array(strtoupper($value), ['ИСТИНА', 'ЛОЖЬ', 'TRUE', 'FALSE'])) {
                        // Boolean
                        $sheet->setCellValue($col . $row, strtoupper($value) === 'ИСТИНА' || strtoupper($value) === 'TRUE' ? 'ИСТИНА' : 'ЛОЖЬ');
                    } elseif ($value !== '' && is_numeric($value)) {
                        // Number
                        $sheet->setCellValue($col . $row, (float)$value);
                        $sheet->getStyle($col . $row)->getNumberFormat()->setFormatCode('0.00');
                    } else {
                        // String
                        $sheet->setCellValue($col . $row, $value);
                    }
                    $col++;
                    $colIndex++;
                }
                $row++;
            }

            // Auto-size columns
            $lastCol = chr(ord('A') + count($headers) - 1);
            foreach (range('A', $lastCol) as $columnID) {
                $sheet->getColumnDimension($columnID)->setAutoSize(true);
            }

            $writer = new Xlsx($spreadsheet);
            $response = new StreamedResponse(function() use ($writer) {
                $writer->save('php://output');
            });

            $response->headers->set('Content-Type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
            $response->headers->set('Content-Disposition', 'attachment;filename="' . basename($filename, '.csv') . '.xlsx"');
            $response->headers->set('Cache-Control', 'max-age=0');
            $response->headers->set('Pragma', 'public');

            return $response;
        } catch (\Exception $e) {
            \Log::error('XLSX Export Error: ' . $e->getMessage(), [
                'file' => $filename,
                'trace' => $e->getTraceAsString()
            ]);
            return redirect('/csv')->with('error', 'Ошибка при экспорте: ' . $e->getMessage());
        }
    }

    private function getCsvFiles(): array
    {
        $dir = '/data/csv';
        $files = [];
        if (is_dir($dir) && is_readable($dir)) {
            $items = @scandir($dir);
            if ($items !== false) {
                foreach ($items as $item) {
                    if ($item === '.' || $item === '..') continue;
                    $filepath = $dir . '/' . $item;
                    if (is_file($filepath) && pathinfo($item, PATHINFO_EXTENSION) === 'csv') {
                        $files[] = [
                            'name' => $item,
                            'size' => filesize($filepath),
                            'modified' => filemtime($filepath),
                        ];
                    }
                }
            }
        }
        usort($files, fn($a, $b) => $b['modified'] <=> $a['modified']);
        return $files;
    }

    private function parseCsv(string $filepath): array
    {
        $headers = [];
        $rows = [];
        
        if (($handle = fopen($filepath, 'r')) !== false) {
            $headers = fgetcsv($handle);
            while (($data = fgetcsv($handle)) !== false) {
                $rows[] = $data;
            }
            fclose($handle);
        }
        
        return ['headers' => $headers, 'rows' => $rows];
    }
}

