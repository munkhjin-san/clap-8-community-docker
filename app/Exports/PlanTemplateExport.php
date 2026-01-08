<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class PlanTemplateExport implements FromArray, WithHeadings, WithColumnWidths, WithStyles
{
    /**
     * @param array<int,array{id:int,code:string,name:string}> $accounts
     * @param array<int,array{label:string,period_index:int}> $periods
     */
    public function __construct(
        private string $projectName,
        private array $accounts,
        private array $periods
    ) {
    }

    public function headings(): array
    {
        $months = array_map(fn($p) => $p['label'], $this->periods);
        return array_merge(['account_code', 'account_name'], $months);
    }

    public function array(): array
    {
        $rows = [];
        foreach ($this->accounts as $acct) {
            $rows[] = array_merge(
                [$acct['code'], $acct['name']],
                array_fill(0, count($this->periods), null)
            );
        }
        return $rows;
    }

    public function columnWidths(): array
    {
        $widths = [
            'A' => 14,
            'B' => 26,
        ];
        $col = 'C';
        foreach ($this->periods as $_) {
            $widths[$col] = 14;
            $col = chr(ord($col) + 1);
        }
        return $widths;
    }

    public function styles(Worksheet $sheet)
    {
        // Bold header row
        $sheet->getStyle('1')->getFont()->setBold(true);
    }
}
