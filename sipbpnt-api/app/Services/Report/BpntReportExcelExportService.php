<?php

declare(strict_types=1);

namespace App\Services\Report;

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Component\HttpFoundation\StreamedResponse;

final class BpntReportExcelExportService
{
    public function download(
        array $snapshot
    ): StreamedResponse {
        $spreadsheet = new Spreadsheet();

        $this->summarySheet(
            $spreadsheet->getActiveSheet(),
            $snapshot
        );

        $this->wilayahSheet(
            $spreadsheet->createSheet(),
            $snapshot['wilayah'] ?? []
        );

        $this->surveyorSheet(
            $spreadsheet->createSheet(),
            $snapshot['surveyors'] ?? []
        );

        $this->eWarungSheet(
            $spreadsheet->createSheet(),
            $snapshot['e_warungs'] ?? []
        );

        $this->participantSheet(
            $spreadsheet->createSheet(),
            $snapshot['participants'] ?? []
        );

        $spreadsheet->setActiveSheetIndex(0);

        $periodCode = preg_replace(
            '/[^A-Za-z0-9_-]+/',
            '-',
            (string) ($snapshot['period']['code'] ?? 'BPNT')
        );

        $filename = 'Laporan-'.$periodCode.'.xlsx';

        return response()->streamDownload(
            static function () use ($spreadsheet): void {
                $writer = new Xlsx($spreadsheet);
                $writer->save('php://output');
                $spreadsheet->disconnectWorksheets();
            },
            $filename,
            [
                'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                'Cache-Control' => 'max-age=0, no-cache, no-store, must-revalidate',
            ]
        );
    }

    private function summarySheet(
        Worksheet $sheet,
        array $snapshot
    ): void {
        $sheet->setTitle('Ringkasan');
        $sheet->fromArray([
            ['LAPORAN FINAL BPNT'],
            ['Periode', $snapshot['period']['name'] ?? '-'],
            ['Kode', $snapshot['period']['code'] ?? '-'],
            ['Tahun', $snapshot['period']['year'] ?? '-'],
            ['Dibuat pada', $snapshot['generated_at'] ?? '-'],
            [],
            ['INDIKATOR', 'JUMLAH'],
            ['Total KPM', $snapshot['summary']['total_kpm'] ?? 0],
            ['Sudah Transaksi', $snapshot['summary']['transacted'] ?? 0],
            ['Belum Transaksi', $snapshot['summary']['pending'] ?? 0],
            ['Meninggal', $snapshot['summary']['deceased'] ?? 0],
            ['Pindah Domisili', $snapshot['summary']['moved_domicile'] ?? 0],
            ['Tidak Mengambil', $snapshot['summary']['not_claimed'] ?? 0],
            ['Persentase Selesai', ($snapshot['summary']['completion_percentage'] ?? 0).'%'],
        ]);

        $sheet->mergeCells('A1:B1');
        $sheet->getStyle('A1:B1')->getFont()->setBold(true)->setSize(16);
        $this->styleHeader($sheet, 'A7:B7');
        $sheet->getColumnDimension('A')->setWidth(28);
        $sheet->getColumnDimension('B')->setWidth(26);
    }

    private function wilayahSheet(
        Worksheet $sheet,
        array $rows
    ): void {
        $sheet->setTitle('Wilayah');
        $sheet->fromArray([[
            'Kecamatan',
            'Kelurahan',
            'Total KPM',
            'Sudah Transaksi',
            'Belum Transaksi',
            'Meninggal',
            'Pindah Domisili',
            'Tidak Mengambil',
        ]]);

        $line = 2;

        foreach ($rows as $row) {
            $sheet->fromArray([[
                $row['kecamatan']['name'] ?? '-',
                $row['kelurahan']['name'] ?? '-',
                $row['total_kpm'] ?? 0,
                $row['transacted'] ?? 0,
                $row['pending'] ?? 0,
                $row['deceased'] ?? 0,
                $row['moved_domicile'] ?? 0,
                $row['not_claimed'] ?? 0,
            ]], null, 'A'.$line);

            $line++;
        }

        $this->styleHeader($sheet, 'A1:H1');
        $this->autoWidth($sheet, 'A', 'H');
    }

    private function surveyorSheet(
        Worksheet $sheet,
        array $rows
    ): void {
        $sheet->setTitle('Surveyor');
        $sheet->fromArray([[
            'Nama Surveyor',
            'Username',
            'Kecamatan Tugas',
            'Kelurahan Tugas',
            'Transaksi Dicatat',
            'Verifikasi Final',
        ]]);

        $line = 2;

        foreach ($rows as $row) {
            $sheet->fromArray([[
                $row['name'] ?? '-',
                $row['username'] ?? '-',
                $row['assignment']['kecamatan']['name'] ?? '-',
                $row['assignment']['kelurahan']['name'] ?? '-',
                $row['transactions'] ?? 0,
                $row['verifications'] ?? 0,
            ]], null, 'A'.$line);

            $line++;
        }

        $this->styleHeader($sheet, 'A1:F1');
        $this->autoWidth($sheet, 'A', 'F');
    }

    private function eWarungSheet(
        Worksheet $sheet,
        array $rows
    ): void {
        $sheet->setTitle('E-Warung');
        $sheet->fromArray([[
            'Nama E-Warung',
            'Jumlah Transaksi',
        ]]);

        $line = 2;

        foreach ($rows as $row) {
            $sheet->fromArray([[
                $row['name'] ?? '-',
                $row['transactions'] ?? 0,
            ]], null, 'A'.$line);

            $line++;
        }

        $this->styleHeader($sheet, 'A1:B1');
        $this->autoWidth($sheet, 'A', 'B');
    }

    private function participantSheet(
        Worksheet $sheet,
        array $rows
    ): void {
        $sheet->setTitle('Detail KPM');
        $sheet->fromArray([[
            'NIK',
            'Nama KPM',
            'Kecamatan Asal',
            'Kelurahan Asal',
            'Alamat',
            'Status',
            'Alasan',
            'Surveyor',
            'E-Warung',
            'Tanggal Penyelesaian',
        ]]);

        $line = 2;

        foreach ($rows as $row) {
            $sheet->fromArray([[
                $row['nik'] ?? '-',
                $row['full_name'] ?? '-',
                $row['wilayah']['kecamatan']['name'] ?? '-',
                $row['wilayah']['kelurahan']['name'] ?? '-',
                $row['address'] ?? '-',
                $row['resolution']['label'] ?? '-',
                $row['resolution']['reason'] ?? '-',
                $row['surveyor']['name'] ?? '-',
                $row['e_warung']['name'] ?? '-',
                $row['resolved_at'] ?? '-',
            ]], null, 'A'.$line);

            $line++;
        }

        $this->styleHeader($sheet, 'A1:J1');
        $this->autoWidth($sheet, 'A', 'J');
        $sheet->getColumnDimension('E')->setWidth(42);
        $sheet->getColumnDimension('G')->setWidth(34);
        $sheet->getStyle('A1:J'.$line)
            ->getAlignment()
            ->setVertical(Alignment::VERTICAL_TOP)
            ->setWrapText(true);
    }

    private function styleHeader(
        Worksheet $sheet,
        string $range
    ): void {
        $style = $sheet->getStyle($range);
        $style->getFont()->setBold(true)->getColor()->setARGB('FFFFFFFF');
        $style->getFill()
            ->setFillType(Fill::FILL_SOLID)
            ->getStartColor()
            ->setARGB('FF006855');
        $style->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $sheet->setAutoFilter($range);
        $sheet->freezePane('A2');
    }

    private function autoWidth(
        Worksheet $sheet,
        string $start,
        string $end
    ): void {
        foreach (range($start, $end) as $column) {
            $sheet->getColumnDimension($column)->setAutoSize(true);
        }
    }
}
