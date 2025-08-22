<?php
// PrintRequestsComparisonExport.php
namespace App\Exports;

use App\Models\PrintRequest;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Events\AfterSheet;

class PrintRequestsComparisonExport implements
    FromCollection,
    WithHeadings,
    WithMapping,
    WithEvents
{
    protected string $firstStartDate;
    protected string $firstEndDate;
    protected string $secondStartDate;
    protected string $secondEndDate;

    public function __construct(
        string $firstStartDate,
        string $firstEndDate,
        string $secondStartDate,
        string $secondEndDate
    ) {
        $this->firstStartDate = $firstStartDate;
        $this->firstEndDate = $firstEndDate;
        $this->secondStartDate = $secondStartDate;
        $this->secondEndDate = $secondEndDate;
    }

    /**
     * @return \Illuminate\Support\Collection
     */
    public function collection()
    {
        // İlk dönem verileri
        $firstPeriodData = PrintRequest::query()
            ->with('requester')
            ->select(
                'requester_id',
                DB::raw('sum(color_copies) as period1_color_copies'),
                DB::raw('sum(bw_copies) as period1_bw_copies'),
                DB::raw('sum(color_copies + bw_copies) as period1_total_copies')
            )
            ->whereBetween('requested_at', [$this->firstStartDate, $this->firstEndDate])
            ->groupBy('requester_id')
            ->get()
            ->keyBy('requester_id');

        // İkinci dönem verileri
        $secondPeriodData = PrintRequest::query()
            ->with('requester')
            ->select(
                'requester_id',
                DB::raw('sum(color_copies) as period2_color_copies'),
                DB::raw('sum(bw_copies) as period2_bw_copies'),
                DB::raw('sum(color_copies + bw_copies) as period2_total_copies')
            )
            ->whereBetween('requested_at', [$this->secondStartDate, $this->secondEndDate])
            ->groupBy('requester_id')
            ->get()
            ->keyBy('requester_id');

        // Tüm requester_id'leri al
        $allRequesterIds = collect($firstPeriodData->keys())
            ->merge($secondPeriodData->keys())
            ->unique();

        // Verileri birleştir
        $combinedData = collect();
        
        foreach ($allRequesterIds as $requesterId) {
            $firstData = $firstPeriodData->get($requesterId);
            $secondData = $secondPeriodData->get($requesterId);
            
            // Requester bilgisini al (hangisinde varsa)
            $requester = $firstData?->requester ?? $secondData?->requester;
            
            $combinedData->push((object) [
                'requester_id' => $requesterId,
                'requester' => $requester,
                'period1_color_copies' => $firstData?->period1_color_copies ?? 0,
                'period1_bw_copies' => $firstData?->period1_bw_copies ?? 0,
                'period1_total_copies' => $firstData?->period1_total_copies ?? 0,
                'period2_color_copies' => $secondData?->period2_color_copies ?? 0,
                'period2_bw_copies' => $secondData?->period2_bw_copies ?? 0,
                'period2_total_copies' => $secondData?->period2_total_copies ?? 0,
                'total_difference' => ($secondData?->period2_total_copies ?? 0) - ($firstData?->period1_total_copies ?? 0),
                'color_difference' => ($secondData?->period2_color_copies ?? 0) - ($firstData?->period1_color_copies ?? 0),
                'bw_difference' => ($secondData?->period2_bw_copies ?? 0) - ($firstData?->period1_bw_copies ?? 0),
            ]);
        }

        // Toplam kopya sayısı farkına göre sırala (azalan)
        return $combinedData->sortByDesc('total_difference');
    }

    /**
     * Excel dosyasının başlık satırını tanımlar.
     */
    public function headings(): array
    {
        $firstPeriodLabel = Carbon::parse($this->firstStartDate)->format('d.m.Y') . ' - ' . Carbon::parse($this->firstEndDate)->format('d.m.Y');
        $secondPeriodLabel = Carbon::parse($this->secondStartDate)->format('d.m.Y') . ' - ' . Carbon::parse($this->secondEndDate)->format('d.m.Y');

        return [
            'Talep Eden Kişi',
            "1. Dönem Renkli\n({$firstPeriodLabel})",
            "1. Dönem S-B\n({$firstPeriodLabel})",
            "1. Dönem Toplam\n({$firstPeriodLabel})",
            "2. Dönem Renkli\n({$secondPeriodLabel})",
            "2. Dönem S-B\n({$secondPeriodLabel})",
            "2. Dönem Toplam\n({$secondPeriodLabel})",
            'Renkli Fark',
            'S-B Fark',
            'Toplam Fark',
        ];
    }

    /**
     * Her bir satırdaki veriyi formatlar.
     */
    public function map($row): array
    {
        return [
            $row->requester ? $row->requester->name : 'Bilinmeyen Talep Eden',
            (int) $row->period1_color_copies,
            (int) $row->period1_bw_copies,
            (int) $row->period1_total_copies,
            (int) $row->period2_color_copies,
            (int) $row->period2_bw_copies,
            (int) $row->period2_total_copies,
            (int) $row->color_difference,
            (int) $row->bw_difference,
            (int) $row->total_difference,
        ];
    }

    /**
     * Sayfa oluşturulduktan sonra çalışacak olayları tanımlar.
     */
    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $firstPeriodLabel = Carbon::parse($this->firstStartDate)->format('d.m.Y') . ' - ' . Carbon::parse($this->firstEndDate)->format('d.m.Y');
                $secondPeriodLabel = Carbon::parse($this->secondStartDate)->format('d.m.Y') . ' - ' . Carbon::parse($this->secondEndDate)->format('d.m.Y');
                $title = "Fotokopi Karşılaştırma Raporu\n{$firstPeriodLabel} vs {$secondPeriodLabel}";

                // Başlık ekle
                $event->sheet->getDelegate()->insertNewRowBefore(1, 2);
                $event->sheet->getDelegate()->setCellValue('A1', $title);
                $event->sheet->getDelegate()->mergeCells('A1:J1');
                $event->sheet->getDelegate()->getStyle('A1')->applyFromArray([
                    'font' => ['bold' => true, 'size' => 14],
                    'alignment' => [
                        'horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER,
                        'wrapText' => true
                    ],
                ]);
                $event->sheet->getDelegate()->getRowDimension('1')->setRowHeight(40);

                // Başlık satırını wrap text yap
                $event->sheet->getDelegate()->getStyle('A3:J3')->applyFromArray([
                    'font' => ['bold' => true],
                    'alignment' => ['wrapText' => true, 'horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER],
                ]);
                $event->sheet->getDelegate()->getRowDimension('3')->setRowHeight(40);

                // Sütun genişliklerini ayarla
                $event->sheet->getDelegate()->getColumnDimension('A')->setWidth(20);
                $event->sheet->getDelegate()->getColumnDimension('B')->setWidth(12);
                $event->sheet->getDelegate()->getColumnDimension('C')->setWidth(12);
                $event->sheet->getDelegate()->getColumnDimension('D')->setWidth(12);
                $event->sheet->getDelegate()->getColumnDimension('E')->setWidth(12);
                $event->sheet->getDelegate()->getColumnDimension('F')->setWidth(12);
                $event->sheet->getDelegate()->getColumnDimension('G')->setWidth(12);
                $event->sheet->getDelegate()->getColumnDimension('H')->setWidth(12);
                $event->sheet->getDelegate()->getColumnDimension('I')->setWidth(12);
                $event->sheet->getDelegate()->getColumnDimension('J')->setWidth(12);

                // Toplam satırını ekle
                $lastRow = $event->sheet->getHighestRow();
                $totalsRow = $lastRow + 1;

                $event->sheet->getDelegate()->setCellValue("A{$totalsRow}", 'Genel Toplam');
                $event->sheet->getDelegate()->setCellValue("B{$totalsRow}", "=SUM(B4:B{$lastRow})");
                $event->sheet->getDelegate()->setCellValue("C{$totalsRow}", "=SUM(C4:C{$lastRow})");
                $event->sheet->getDelegate()->setCellValue("D{$totalsRow}", "=SUM(D4:D{$lastRow})");
                $event->sheet->getDelegate()->setCellValue("E{$totalsRow}", "=SUM(E4:E{$lastRow})");
                $event->sheet->getDelegate()->setCellValue("F{$totalsRow}", "=SUM(F4:F{$lastRow})");
                $event->sheet->getDelegate()->setCellValue("G{$totalsRow}", "=SUM(G4:G{$lastRow})");
                $event->sheet->getDelegate()->setCellValue("H{$totalsRow}", "=SUM(H4:H{$lastRow})");
                $event->sheet->getDelegate()->setCellValue("I{$totalsRow}", "=SUM(I4:I{$lastRow})");
                $event->sheet->getDelegate()->setCellValue("J{$totalsRow}", "=SUM(J4:J{$lastRow})");

                // Toplam satırını kalın yap
                $event->sheet->getDelegate()->getStyle("A{$totalsRow}:J{$totalsRow}")->getFont()->setBold(true);

                // Fark sütunlarına renk kodlaması ekle (pozitif yeşil, negatif kırmızı)
                $dataRange = "H4:J{$lastRow}";
                $event->sheet->getDelegate()->getStyle($dataRange)->applyFromArray([
                    'conditionalFormatting' => [
                        [
                            'type' => \PhpOffice\PhpSpreadsheet\Style\Conditional::CONDITION_CELLIS,
                            'operatorType' => \PhpOffice\PhpSpreadsheet\Style\Conditional::OPERATOR_GREATERTHAN,
                            'operand1' => 0,
                            'style' => [
                                'fill' => [
                                    'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                                    'color' => ['argb' => 'FFC6EFCE']
                                ]
                            ]
                        ],
                        [
                            'type' => \PhpOffice\PhpSpreadsheet\Style\Conditional::CONDITION_CELLIS,
                            'operatorType' => \PhpOffice\PhpSpreadsheet\Style\Conditional::OPERATOR_LESSTHAN,
                            'operand1' => 0,
                            'style' => [
                                'fill' => [
                                    'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                                    'color' => ['argb' => 'FFFFC7CE']
                                ]
                            ]
                        ]
                    ]
                ]);
            },
        ];
    }
}