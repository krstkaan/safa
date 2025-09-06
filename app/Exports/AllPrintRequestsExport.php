<?php

namespace App\Exports;

use App\Models\PrintRequest;
use Carbon\Carbon;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Events\AfterSheet;

class AllPrintRequestsExport implements
    FromCollection,
    WithHeadings,
    WithMapping,
    WithEvents
{
    protected ?string $startDate;
    protected ?string $endDate;

    public function __construct(?string $startDate = null, ?string $endDate = null)
    {
        $this->startDate = $startDate;
        $this->endDate = $endDate;
    }

    /**
     * @return \Illuminate\Support\Collection
     */
    public function collection()
    {
        $query = PrintRequest::query()
            ->with(['requester', 'approver'])
            ->orderBy('requested_at', 'desc');

        // Eğer tarih aralığı belirtilmişse filtreleme yap
        if ($this->startDate && $this->endDate) {
            $query->whereBetween('requested_at', [$this->startDate, $this->endDate]);
        }

        return $query->get();
    }

    /**
     * Excel dosyasının başlık satırını tanımlar.
     */
    public function headings(): array
    {
        return [
            'ID',
            'Talep Eden Kişi',
            'Onaylayan Kişi',
            'Renkli Kopya',
            'Siyah-Beyaz Kopya',
            'Toplam Kopya',
            'Açıklama',
            'Talep Tarihi',
            'Durum',
        ];
    }

    /**
     * Her bir satırdaki veriyi formatlar.
     */
    public function map($printRequest): array
    {
        return [
            $printRequest->id,
            $printRequest->requester ? $printRequest->requester->name : 'Bilinmeyen Talep Eden',
            $printRequest->approver ? $printRequest->approver->name : 'Henüz Onaylanmamış',
            (int) $printRequest->color_copies,
            (int) $printRequest->bw_copies,
            (int) ($printRequest->color_copies + $printRequest->bw_copies),
            $printRequest->description ?? 'Açıklama yok',
            $printRequest->requested_at ? Carbon::parse($printRequest->requested_at)->format('d.m.Y H:i') : 'Tarih belirtilmemiş',
            $this->getStatusText($printRequest),
        ];
    }

    /**
     * Print request durumunu Türkçe metne çevirir.
     */
    private function getStatusText($printRequest): string
    {
        if ($printRequest->deleted_at) {
            return 'Silindi';
        }
        
        if ($printRequest->approver_id) {
            return 'Onaylandı';
        }
        
        return 'Beklemede';
    }

    /**
     * Sayfa oluşturulduktan sonra çalışacak olayları tanımlar.
     */
    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                // Rapor başlığını ekleme
                $title = $this->getReportTitle();

                $event->sheet->getDelegate()->insertNewRowBefore(1, 1);
                $event->sheet->getDelegate()->setCellValue('A1', $title);
                $event->sheet->getDelegate()->mergeCells('A1:I1');
                $event->sheet->getDelegate()->getStyle('A1')->applyFromArray([
                    'font' => ['bold' => true, 'size' => 14],
                    'alignment' => ['horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER],
                ]);
                $event->sheet->getDelegate()->getRowDimension('1')->setRowHeight(20);
                $event->sheet->getDelegate()->getRowDimension('2')->setRowHeight(20);

                // Başlık satırını kalın yap
                $event->sheet->getDelegate()->getStyle('A2:I2')->getFont()->setBold(true);

                // Genel toplam satırını ekleme
                $lastRow = $event->sheet->getHighestRow();
                $totalsRow = $lastRow + 1;

                $event->sheet->getDelegate()->setCellValue("A{$totalsRow}", 'Genel Toplam');
                $event->sheet->getDelegate()->setCellValue("D{$totalsRow}", "=SUM(D3:D{$lastRow})");
                $event->sheet->getDelegate()->setCellValue("E{$totalsRow}", "=SUM(E3:E{$lastRow})");
                $event->sheet->getDelegate()->setCellValue("F{$totalsRow}", "=SUM(F3:F{$lastRow})");

                // Toplam satırını kalın yap
                $event->sheet->getDelegate()->getStyle("A{$totalsRow}:I{$totalsRow}")->getFont()->setBold(true);

                // Sütun genişliklerini ayarla
                $event->sheet->getDelegate()->getColumnDimension('A')->setWidth(8);
                $event->sheet->getDelegate()->getColumnDimension('B')->setWidth(20);
                $event->sheet->getDelegate()->getColumnDimension('C')->setWidth(20);
                $event->sheet->getDelegate()->getColumnDimension('D')->setWidth(12);
                $event->sheet->getDelegate()->getColumnDimension('E')->setWidth(15);
                $event->sheet->getDelegate()->getColumnDimension('F')->setWidth(12);
                $event->sheet->getDelegate()->getColumnDimension('G')->setWidth(30);
                $event->sheet->getDelegate()->getColumnDimension('H')->setWidth(15);
                $event->sheet->getDelegate()->getColumnDimension('I')->setWidth(12);

                // Otomatik filtre ekle
                $event->sheet->getDelegate()->setAutoFilter('A2:I' . $lastRow);
            },
        ];
    }

    /**
     * Rapor başlığını oluşturur.
     */
    private function getReportTitle(): string
    {
        if ($this->startDate && $this->endDate) {
            $formattedStartDate = Carbon::parse($this->startDate)->format('d.m.Y');
            $formattedEndDate = Carbon::parse($this->endDate)->format('d.m.Y');
            return "Tüm Fotokopi Talepleri Raporu ($formattedStartDate - $formattedEndDate)";
        }
        
        return "Tüm Fotokopi Talepleri Raporu";
    }
}
