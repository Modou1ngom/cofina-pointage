<?php

namespace App\Exports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class PointageFicheHorairesExport implements FromCollection, ShouldAutoSize, WithHeadings, WithMapping, WithStyles
{
    /**
     * @param  Collection<int, array<string, string>>  $rows
     */
    public function __construct(
        protected Collection $rows,
    ) {}

    public function collection(): Collection
    {
        return $this->rows;
    }

    /**
     * @return array<int, string>
     */
    public function headings(): array
    {
        return [
            'Name',
            'Date',
            'H. Arrivee',
            'H. Départ',
            'H. ajust arrivée',
            'H. ajust départ',
            'Total',
            'Total ajust.',
            'Total ajust.',
        ];
    }

    /**
     * @param  array<string, string>  $row
     * @return array<int, string>
     */
    public function map($row): array
    {
        return [
            $row['name'] ?? '',
            $row['date'] ?? '',
            $row['h_arrivee'] ?? '',
            $row['h_depart'] ?? '',
            $row['h_ajust_arrivee'] ?? '',
            $row['h_ajust_depart'] ?? '',
            $row['total'] ?? '',
            $row['total_ajust_calc'] ?? '',
            $row['total_ajust_journee'] ?? '',
        ];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function styles(Worksheet $sheet): array
    {
        $lastCol = 'I';
        $lastRow = max(1, $this->rows->count() + 1);

        $sheet->getStyle("A1:{$lastCol}1")->applyFromArray([
            'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['rgb' => 'DC143C'],
            ],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
                'vertical' => Alignment::VERTICAL_CENTER,
            ],
        ]);

        if ($lastRow > 1) {
            $sheet->getStyle("B2:{$lastCol}{$lastRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $sheet->getStyle("A2:A{$lastRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT);
        }

        return [];
    }
}
