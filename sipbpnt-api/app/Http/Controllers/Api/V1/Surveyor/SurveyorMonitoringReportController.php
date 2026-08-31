<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Surveyor;

use App\Http\Controllers\Controller;
use App\Http\Requests\Surveyor\UpdateSurveyorMonitoringReportRequest;
use App\Services\Surveyor\SurveyorMonitoringReportPdfService;
use App\Services\Surveyor\SurveyorMonitoringReportService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Str;

final class SurveyorMonitoringReportController
    extends Controller
{
    public function __construct(
        private readonly SurveyorMonitoringReportService $service,
        private readonly SurveyorMonitoringReportPdfService $pdf,
    ) {}

    public function show(
        Request $request
    ): JsonResponse {
        return response()->json([
            'data' => $this->service->show(
                $request->user()
            ),
        ]);
    }

    public function update(
        UpdateSurveyorMonitoringReportRequest $request
    ): JsonResponse {
        return response()->json([
            'message'
                => 'Pengaturan laporan monitoring berhasil disimpan.',

            'data' => $this->service->update(
                $request->user(),
                $request->validated(),
                $request->ip(),
                $request->userAgent()
            ),
        ]);
    }

    public function pdf(
        Request $request
    ): Response {
        $data = $this->service->pdfData(
            $request->user()
        );

        $content = $this->pdf->render(
            $data
        );

        /*
         * Aktivitas unduh hanya dicatat
         * dalam audit log.
         *
         * Proses ini tidak mengunci laporan.
         */
        $this->service->recordPdfDownload(
            $request->user(),
            $data,
            $request->ip(),
            $request->userAgent()
        );

        $filename = 'laporan-monitoring-'
            .Str::slug(
                (string) $data[
                    'assignment'
                ][
                    'kelurahan'
                ][
                    'name'
                ]
            )
            .'-'
            .Str::slug(
                (string) $data[
                    'period'
                ][
                    'name'
                ]
            )
            .'.pdf';

        return response(
            $content,
            200,
            [
                'Content-Type'
                    => 'application/pdf',

                'Content-Disposition'
                    => 'attachment; filename="'
                        .$filename
                        .'"',

                'Content-Length'
                    => (string) strlen(
                        $content
                    ),

                'Cache-Control'
                    => 'private, no-store, max-age=0',
            ]
        );
    }
}