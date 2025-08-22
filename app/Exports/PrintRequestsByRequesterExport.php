<?php

namespace App\Exports;

use App\Models\PrintRequest;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Events\AfterSheet;

class PrintRequestsByRequesterExport implements
    FromCollection,
    WithHeadings,
    WithMapping,
    WithEvents
{
    protected string $startDate;
    protected string $endDate;

    public function __construct(string $startDate, string $endDate)
    {
        $this->startDate = $startDate;
        $this->endDate = $endDate;
    }

    /**
    * @return \Illuminate\Support\Collection
    */
    public function collection()
    {
        return PrintRequest::query()
            ->with('requester')
            ->select(
                'requester_id',
                DB::raw('sum(color_copies) as total_color_copies'),
                DB::raw('sum(bw_copies) as total_bw_copies'),
                // YENİ: Renkli ve siyah-beyaz kopyaların toplamını doğrudan SQL sorgusunda hesapla
                DB::raw('sum(color_copies + bw_copies) as total_all_copies')
            )
            ->whereBetween('requested_at', [$this->startDate, $this->endDate])
            ->groupBy('requester_id')
            // Toplam kopya sayısına göre çoktan aza doğru sıralayalım
            ->orderBy('total_all_copies', 'desc')
            ->get();
    }

    /**
     * Excel dosyasının başlık satırını tanımlar.
     */
    public function headings(): array
    {
        return [
            'Talep Eden Kişi',
            'Renkli Kopya',
            'Siyah-Beyaz Kopya',
            // YENİ: Yeni sütun için başlık eklendi
            'Toplam Kopya',
        ];
    }

    /**
     * Her bir satırdaki veriyi formatlar.
     */
    public function map($row): array
    {
        return [
            $row->requester ? $row->requester->name : 'Bilinmeyen Talep Eden',
            (int) $row->total_color_copies,
            (int) $row->total_bw_copies,
            // YENİ: Yeni toplam sütununun verisi eklendi
            (int) $row->total_all_copies,
        ];
    }

    /**
     * Sayfa oluşturulduktan sonra çalışacak olayları tanımlar.
     */
    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                // --- 1. Adım: Rapor Başlığını Ekleme (Mevcut Kod) ---
                $formattedStartDate = Carbon::parse($this->startDate)->format('d.m.Y');
                $formattedEndDate = Carbon::parse($this->endDate)->format('d.m.Y');
                $title = "Fotokopi Raporu ($formattedStartDate - $formattedEndDate)";

                $event->sheet->getDelegate()->insertNewRowBefore(1, 1);
                $event->sheet->getDelegate()->setCellValue('A1', $title);
                // GÜNCELLENDİ: Artık 4 sütun olduğu için D1'e kadar birleştir
                $event->sheet->getDelegate()->mergeCells('A1:D1');
                $event->sheet->getDelegate()->getStyle('A1')->applyFromArray([
                    'font' => ['bold' => true, 'size' => 14],
                    'alignment' => ['horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER],
                ]);
                $event->sheet->getDelegate()->getRowDimension('1')->setRowHeight(20);
                $event->sheet->getDelegate()->getRowDimension('2')->setRowHeight(20);

                // --- 2. Adım: Genel Toplam Satırını Ekleme (Yeni Kod) ---

                // Verilerin bittiği son satırın numarasını al
                $lastRow = $event->sheet->getHighestRow();
                // Toplamların yazılacağı yeni satırın numarasını belirle
                $totalsRow = $lastRow + 1;

                // A sütununa "Genel Toplam" etiketini yaz
                $event->sheet->getDelegate()->setCellValue("A{$totalsRow}", 'Genel Toplam');

                // B, C ve D sütunları için Excel'in kendi SUM formülünü kullan
                // Bu sayede dosyayı açan kişi verileri değiştirirse toplamlar da güncellenir
                $event->sheet->getDelegate()->setCellValue("B{$totalsRow}", "=SUM(B3:B{$lastRow})");
                $event->sheet->getDelegate()->setCellValue("C{$totalsRow}", "=SUM(C3:C{$lastRow})");
                $event->sheet->getDelegate()->setCellValue("D{$totalsRow}", "=SUM(D3:D{$lastRow})");

                // Toplam satırını kalın yap
                $event->sheet->getDelegate()->getStyle("A{$totalsRow}:D{$totalsRow}")->getFont()->setBold(true);
            },
        ];
    }
}