<?php

declare(strict_types=1);

namespace Tests\Unit\Services;

use App\Services\Surveyor\SurveyorMonitoringReportPdfService;
use PHPUnit\Framework\TestCase;

final class SurveyorMonitoringReportPdfServiceTest
    extends TestCase
{
    public function test_pdf_uses_monitoring_page_and_six_list_pages_for_130_kpm(): void
    {
        $participants = [];

        for (
            $number = 1;
            $number <= 130;
            $number++
        ) {
            $participants[] = [
                'id' => $number,
                'number' => $number,
                'name' => 'KPM '.$number,
                'nik' => '3576********'.str_pad(
                    (string) $number,
                    4,
                    '0',
                    STR_PAD_LEFT
                ),
                'address' =>
                    'JL. TEST, RT: 001, RW: 002',
                'period' => 'Maret 2026',
                'amount' => 150000,
                'status' => [
                    'code' => 'taking',
                    'label' => 'Mengambil',
                    'reason' => null,
                ],
                'e_warung' =>
                    'E-Warung Test',
            ];
        }

        $pdf = (
            new SurveyorMonitoringReportPdfService()
        )->render([
            'id' => 1,
            'period' => [
                'id' => 1,
                'code' => 'BPNT-2026-03',
                'name' => 'Maret 2026',
                'year' => 2026,
                'allocation_label' =>
                    'Maret 2026',
            ],
            'surveyor' => [
                'id' => 1,
                'name' => 'SURVEYOR TEST',
            ],
            'assignment' => [
                'id' => 1,
                'kecamatan' => [
                    'id' => 1,
                    'name' =>
                        'PRAJURIT KULON',
                ],
                'kelurahan' => [
                    'id' => 1,
                    'name' =>
                        'PRAJURIT KULON',
                ],
            ],
            'editable' => [
                'commodities' => [
                    'Beras',
                    'Telur',
                ],
                'social_officer_name' =>
                    'NAMA KASI',
                'distribution_assistant_name' =>
                    'NAMA PENDAMPING',
            ],
            'summary' => [
                'total_kpm' => 130,
                'taking' => 130,
                'not_taking' => 0,
                'deceased' => 0,
                'moved_domicile' => 0,
                'not_claimed' => 0,
                'pending' => 0,
                'total_balance' => 19500000,
                'e_warungs' => [
                    'E-Warung Test',
                ],
                'reason_summary' => [],
                'evaluation' =>
                    'Seluruh KPM telah mengambil bantuan.',
            ],
            'updated_at' => null,
            'participants' => $participants,
        ]);

        $this->assertStringStartsWith(
            '%PDF-1.4',
            $pdf
        );

        $this->assertStringContainsString(
            '/Type /Pages /Count 7',
            $pdf
        );

        $this->assertStringContainsString(
            'Halaman 6 dari 6',
            $pdf
        );

        $this->assertStringContainsString(
            'KPM 130',
            $pdf
        );

        $this->assertStringContainsString(
            'dr. FARIDA MARIANA, M.Kes.',
            $pdf
        );

        $this->assertStringContainsString(
            'BASUKI RACHMANTO, SH., MM',
            $pdf
        );

        $this->assertStringContainsString(
            'NURWIDIA KUSUMA DEWI, SE',
            $pdf
        );
    }
}