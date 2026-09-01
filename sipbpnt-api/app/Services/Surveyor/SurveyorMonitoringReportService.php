<?php

declare(strict_types=1);

namespace App\Services\Surveyor;

use App\Contracts\Repositories\AuditLogRepositoryInterface;
use App\Contracts\Repositories\BpntPeriodRepositoryInterface;
use App\Contracts\Repositories\SurveyorAssignmentRepositoryInterface;
use App\Contracts\Repositories\SurveyorMonitoringReportRepositoryInterface;
use App\Enums\KpmVerificationStatus;
use App\Models\BpntParticipant;
use App\Models\BpntPeriod;
use App\Models\SurveyorAssignment;
use App\Models\SurveyorMonitoringReport;
use App\Models\User;
use App\Support\Security\SensitiveIdentity;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;

final class SurveyorMonitoringReportService
{
    public function __construct(
        private readonly BpntPeriodRepositoryInterface $periods,
        private readonly SurveyorAssignmentRepositoryInterface $assignments,
        private readonly SurveyorMonitoringReportRepositoryInterface $reports,
        private readonly AuditLogRepositoryInterface $auditLogs,
        private readonly SensitiveIdentity $identity,
    ) {}

    public function show(User $surveyor): array
    {
        [
            'period' => $period,
            'assignment' => $assignment,
        ] = $this->requireOperationalContext($surveyor);

        $report = $this->reports->findForSurveyorInPeriod(
            (int) $period->id,
            (int) $surveyor->id
        );

        $participants = $this->reports->confirmedParticipants(
            (int) $period->id,
            (int) $assignment->kelurahan_id
        );

        return $this->serialize(
            $surveyor,
            $period,
            $assignment,
            $report,
            $participants
        );
    }

    public function update(
        User $surveyor,
        array $data,
        ?string $ipAddress,
        ?string $userAgent
    ): array {
        [
            'period' => $period,
            'assignment' => $assignment,
        ] = $this->requireOperationalContext($surveyor);

        $report = $this->reports->saveConfiguration(
            (int) $period->id,
            (int) $assignment->id,
            (int) $surveyor->id,
            (int) $assignment->kelurahan_id,
            $data
        );

        $this->auditLogs->record([
            'user_id' => $surveyor->id,
            'action' => 'surveyor.monitoring_report.updated',
            'auditable_type' => SurveyorMonitoringReport::class,
            'auditable_id' => $report->id,
            'metadata' => [
                'period_id' => $period->id,
                'assignment_id' => $assignment->id,
                'kelurahan_id' => $assignment->kelurahan_id,
                'commodities' => $report->commodities,
            ],
            'ip_address' => $ipAddress,
            'user_agent' => $userAgent,
        ]);

        $participants = $this->reports->confirmedParticipants(
            (int) $period->id,
            (int) $assignment->kelurahan_id
        );

        return $this->serialize(
            $surveyor,
            $period,
            $assignment,
            $report,
            $participants
        );
    }

    public function pdfData(User $surveyor): array
    {
        [
            'period' => $period,
            'assignment' => $assignment,
        ] = $this->requireOperationalContext($surveyor);

        $report = $this->reports->findForSurveyorInPeriod(
            (int) $period->id,
            (int) $surveyor->id
        );

        if (
            ! $report instanceof SurveyorMonitoringReport
            || count($report->commodities ?? []) === 0
        ) {
            throw ValidationException::withMessages([
                'report' => [
                    'Simpan minimal satu komoditas sebelum mengunduh PDF.',
                ],
            ]);
        }

        $participants = $this->reports->confirmedParticipants(
            (int) $period->id,
            (int) $assignment->kelurahan_id
        );

        $data = $this->serialize(
            $surveyor,
            $period,
            $assignment,
            $report,
            $participants
        );

        $data['participants'] = $participants
            ->values()
            ->map(
                fn (
                    BpntParticipant $participant,
                    int $index
                ): array => $this->serializeParticipant(
                    $participant,
                    $period,
                    $index + 1
                )
            )
            ->values()
            ->all();

        return $data;
    }

    public function recordPdfDownload(
        User $surveyor,
        array $data,
        ?string $ipAddress,
        ?string $userAgent
    ): void {
        $this->auditLogs->record([
            'user_id' => $surveyor->id,
            'action' => 'surveyor.monitoring_report.pdf_downloaded',
            'auditable_type' => SurveyorMonitoringReport::class,
            'auditable_id' => $data['id'],
            'metadata' => [
                'period_id' => $data['period']['id'],
                'assignment_id' => $data['assignment']['id'],
                'kelurahan_id' => $data['assignment']['kelurahan']['id'],
                'total_kpm' => $data['summary']['total_kpm'],
            ],
            'ip_address' => $ipAddress,
            'user_agent' => $userAgent,
        ]);
    }

    private function serialize(
        User $surveyor,
        BpntPeriod $period,
        SurveyorAssignment $assignment,
        ?SurveyorMonitoringReport $report,
        Collection $participants
    ): array {
        $summary = $this->summary($participants);

        return [
            'id' => $report?->id,
            'period' => [
                'id' => (int) $period->id,
                'code' => (string) $period->code,
                'name' => (string) $period->name,
                'year' => (int) $period->year,
                'allocation_label' => $this->allocationLabel($period),
            ],
            'surveyor' => [
                'id' => (int) $surveyor->id,
                'name' => (string) $surveyor->name,
            ],
            'assignment' => [
                'id' => (int) $assignment->id,
                'kecamatan' => [
                    'id' => (int) $assignment
                        ->kelurahan
                        ->kecamatan
                        ->id,
                    'name' => (string) $assignment
                        ->kelurahan
                        ->kecamatan
                        ->name,
                ],
                'kelurahan' => [
                    'id' => (int) $assignment->kelurahan->id,
                    'name' => (string) $assignment->kelurahan->name,
                ],
            ],
            'editable' => [
                'commodities' => array_values(
                    $report?->commodities ?? []
                ),
                'social_officer_name' =>
                    $report?->social_officer_name,
                'distribution_assistant_name' =>
                    $report instanceof SurveyorMonitoringReport
                        ? $report->distribution_assistant_name
                        : (string) $surveyor->name,
            ],
            'summary' => $summary,
            'updated_at' => $report?->updated_at
                ?->timezone('Asia/Jakarta')
                ->toIso8601String(),
        ];
    }

    private function summary(Collection $participants): array
    {
        $taking = 0;
        $deceased = 0;
        $movedDomicile = 0;
        $notClaimed = 0;
        $pending = 0;
        $totalBalance = 0;
        $eWarungs = collect();
        $reasons = collect();

        foreach ($participants as $participant) {
            $totalBalance += (int) $participant->entitlement_amount;

            $transaction = $participant->transactions->first();

            if ($transaction !== null) {
                $taking++;

                if ($transaction->eWarung !== null) {
                    $eWarungs->push(
                        (string) $transaction->eWarung->name
                    );
                }

                continue;
            }

            $verification = $participant->activeVerification;

            if ($verification === null) {
                $pending++;

                continue;
            }

            $status = $verification->status;

            if ($status === KpmVerificationStatus::DECEASED) {
                $deceased++;
                $reasons->push('Meninggal');
            } elseif (
                $status === KpmVerificationStatus::MOVED_DOMICILE
            ) {
                $movedDomicile++;
                $reasons->push('Pindah Domisili');
            } else {
                $notClaimed++;

                $reason = trim(
                    (string) $verification->reason
                );

                $reasons->push(
                    $reason !== ''
                        ? $reason
                        : 'Tidak Transaksi'
                );
            }
        }

        $reasonSummary = $reasons
            ->countBy()
            ->map(
                fn (int $count, string $label): array => [
                    'label' => $label,
                    'count' => $count,
                ]
            )
            ->values()
            ->all();

        $notTaking = $deceased
            + $movedDomicile
            + $notClaimed;

        return [
            'total_kpm' => $participants->count(),
            'taking' => $taking,
            'not_taking' => $notTaking,
            'deceased' => $deceased,
            'moved_domicile' => $movedDomicile,
            'not_claimed' => $notClaimed,
            'pending' => $pending,
            'total_balance' => $totalBalance,
            'e_warungs' => $eWarungs
                ->filter()
                ->unique()
                ->sort()
                ->values()
                ->all(),
            'reason_summary' => $reasonSummary,
            'evaluation' => $this->evaluation(
                $participants->count(),
                $taking,
                $notTaking,
                $pending,
                $reasonSummary
            ),
        ];
    }

    private function serializeParticipant(
        BpntParticipant $participant,
        BpntPeriod $period,
        int $sequence
    ): array {
        $transaction = $participant->transactions->first();
        $verification = $participant->activeVerification;

        $status = [
            'code' => 'pending',
            'label' => 'Belum Transaksi',
            'reason' => null,
        ];

        if ($transaction !== null) {
            $status = [
                'code' => 'taking',
                'label' => 'Transaksi',
                'reason' => null,
            ];
        } elseif ($verification !== null) {
            $verificationStatus = $verification->status;

            $reason = $verificationStatus
                === KpmVerificationStatus::NOT_CLAIMED
                    ? trim((string) $verification->reason)
                    : null;

            $status = [
                'code' => $verificationStatus->value,
                'label' => $verificationStatus->label(),
                'reason' => $reason !== ''
                    ? $reason
                    : null,
            ];
        }

        $address = collect([
            trim((string) $participant->kpm->address),
            $participant->kpm->rt
                ? 'RT: '.$participant->kpm->rt
                : null,
            $participant->kpm->rw
                ? 'RW: '.$participant->kpm->rw
                : null,
        ])
            ->filter()
            ->implode(', ');

        return [
            'id' => (int) $participant->id,
            'number' => $sequence,
            'name' => (string) $participant->kpm->full_name,
            'nik' => (string) $this->identity->maskCiphertext(
                $participant->kpm->nik_ciphertext
            ),
            'address' => $address,
            'period' => $this->allocationLabel($period),
            'amount' => (int) $participant->entitlement_amount,
            'status' => $status,
            'e_warung' => $transaction?->eWarung
                ? (string) $transaction->eWarung->name
                : null,
        ];
    }

    private function evaluation(
        int $total,
        int $taking,
        int $notTaking,
        int $pending,
        array $reasonSummary
    ): string {
        $sentence = 'Dari '.$total
            .' KPM pada BNBA, '.$taking
            .' KPM telah transaksi bantuan, '.$notTaking
            .' KPM tidak transaksi, dan '.$pending
            .' KPM belum transaksi.';

        if ($reasonSummary === []) {
            return $sentence;
        }

        $reasons = collect($reasonSummary)
            ->map(
                fn (array $reason): string =>
                    $reason['label']
                    .': '
                    .$reason['count']
                    .' KPM'
            )
            ->implode('; ');

        return $sentence
            .' Keterangan tidak transaksi: '
            .$reasons
            .'.';
    }

    private function allocationLabel(BpntPeriod $period): string
    {
        $name = trim((string) $period->name);

        if (
            str_contains(
                mb_strtolower($name),
                (string) $period->year
            )
        ) {
            return $name;
        }

        return trim(
            $name.' '.$period->year
        );
    }

    private function requireOperationalContext(
        User $surveyor
    ): array {
        $period = $this->periods->active();

        if (! $period instanceof BpntPeriod) {
            throw ValidationException::withMessages([
                'period' => [
                    'Belum ada periode BPNT aktif. Hubungi Admin Dinsos.',
                ],
            ]);
        }

        $assignment = $this->assignments->findForSurveyorInPeriod(
            (int) $period->id,
            (int) $surveyor->id
        );

        if (! $assignment instanceof SurveyorAssignment) {
            throw ValidationException::withMessages([
                'assignment' => [
                    'Anda belum memiliki wilayah tugas pada periode aktif.',
                ],
            ]);
        }

        return [
            'period' => $period,
            'assignment' => $assignment,
        ];
    }
}