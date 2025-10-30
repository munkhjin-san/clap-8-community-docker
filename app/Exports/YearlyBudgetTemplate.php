<?php

namespace App\Exports;

use Carbon\Carbon;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Events\BeforeExport;
use Maatwebsite\Excel\Events\AfterSheet;
use Maatwebsite\Excel\Files\LocalTemporaryFile;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Excel as ExcelWriter;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;
use PhpOffice\PhpSpreadsheet\Cell\DataValidation;

class YearlyBudgetTemplate implements WithEvents
{
    public function __construct(
        private int $fiscalYear,    // e.g. 2025
        private array $rows = [],
        private string $projectName,
        private int $fiscalMonth,    // your data rows if needed
    ) {}

    private function fyMonths(): array
    {
        $start = Carbon::create($this->fiscalYear, $this->fiscalMonth, 1);
        $months = [];
        for ($i = 0; $i < 12; $i++) {
            $months[] = $start->copy()->addMonths($i)->format('Y-m');
        }
        return $months;
    }

    public function headings(): array
    {
        return array_merge([$this->projectName], $this->fyMonths());
    }
    public function array(): array
    {
        return $this->rows;
    }
    public function columnWidths(): array
    {
        return [
            'A' => 30,
            'B' => 15, 'C' => 15, 'D' => 15, 'E' => 15,
            'F' => 15, 'G' => 15, 'H' => 15, 'I' => 15,
            'J' => 15, 'K' => 15, 'L' => 15, 'M' => 15,
        ];
    }
    public function styles(Worksheet $sheet)
    {
        // Header row bold
        $sheet->getStyle('1')->getFont()->setBold(true);
    }
    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $e) {
                $sheet = $e->sheet->getDelegate();
                $highestRow = $sheet->getHighestRow();    // includes headings
                $highestCol = $sheet->getHighestColumn(); // should be 'M'
                $months = $this->fyMonths();
                $headers = $this->headings();
                foreach ($headers as $i => $label) {
                    $sheet->setCellValueByColumnAndRow(1 + $i, 1, $label);
                }
            

                foreach ($this->array() as $r => $cat) {
                    $row = 2 + $r;
                    $sheet->setCellValue("A${row}", $cat);
                }
                $lastRow = 1 + count($this->rows);
                $sheet->getStyle("A1:M1")->applyFromArray([
                    'fill' => ['fillType' => 'solid', 'startColor' => ['rgb' => 'F2F2F2']],
                    'font' => ['bold' => true],
                    'alignment' => ['horizontal' => 'center', 'vertical' => 'center'],
                ]);
                $sheet->getStyle("A1:M{$lastRow}")->applyFromArray([
                    'borders' => ['allBorders' => ['borderStyle' => 'thin', 'color' => ['rgb' => 'E0E0E0']]],
                ]);
                $sheet->getStyle("B2:M{$lastRow}")
                      ->getNumberFormat()->setFormatCode(NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1);

                $sheet->freezePane('A2');
                $sheet->setAutoFilter("A1:M1");
                $sheet->getColumnDimension('A')->setWidth(24);
                foreach (range('B','M') as $col) $sheet->getColumnDimension($col)->setWidth(12);

            },
        ];
    }
}
