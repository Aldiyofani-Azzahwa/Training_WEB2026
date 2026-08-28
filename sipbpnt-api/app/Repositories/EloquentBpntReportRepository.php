<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Contracts\Repositories\BpntReportRepositoryInterface;
use App\Enums\BnbaImportStatus;
use App\Enums\BpntReportStatus;
use App\Enums\KpmVerificationStatus;
use App\Models\BpntParticipant;
use App\Models\BpntReport;
use App\Models\BpntTransaction;
use App\Models\KpmVerification;
use App\Models\SurveyorAssignment;
use App\Support\Security\SensitiveIdentity;

final class EloquentBpntReportRepository
    implements BpntReportRepositoryInterface
{
    public function __construct(
        private readonly SensitiveIdentity $identity,
    ) {}

    public function findByPeriod(
        int $periodId
    ): ?BpntReport {
        return BpntReport::query()
            ->with([
                'period',
                'finalizedBy',
            ])
            ->where('period_id', $periodId)
            ->first();
    }

    public function findSummaryByPeriod(
        int $periodId
    ): ?BpntReport {
        return BpntReport::query()
            ->select([
                'id',
                'period_id',
                'status',
                'summary',
                'finalized_by',
                'finalized_at',
            ])
            ->with('finalizedBy:id,name')
            ->where('period_id', $periodId)
            ->first();
    }

    public function findByPeriodForUpdate(
        int $periodId
    ): ?BpntReport {
        return BpntReport::query()
            ->where('period_id', $periodId)
            ->lockForUpdate()
            ->first();
    }

    public function isFinalForPeriod(
        int $periodId
    ): bool {
        return BpntReport::query()
            ->where('period_id', $periodId)
            ->where(
                'status',
                BpntReportStatus::FINAL->value
            )
            ->exists();
    }

    public function createFinal(
        int $periodId,
        int $managerId,
        array $snapshot
    ): BpntReport {
        $report = BpntReport::query()->create([
            'period_id' => $periodId,
            'status' => BpntReportStatus::FINAL,
            'summary' => $snapshot['summary'],
            'snapshot' => $snapshot,
            'finalized_by' => $managerId,
            'finalized_at' => now(),
        ]);

        return $this->findByPeriod(
            (int) $report->period_id
        ) ?? $report;
    }

    public function buildSnapshot(
        int $periodId
    ): array {
        $participants = $this->snapshotParticipants(
            $periodId
        );

        $summary = $this->emptySnapshotSummary(
            $participants->count()
        );

        $wilayah = [];
        $surveyors = $this->initialSurveyors($periodId);
        $eWarungs = [];
        $details = [];

        foreach ($participants as $participant) {
            /** @var BpntTransaction|null $transaction */
            $transaction = $participant
                ->transactions
                ->first();

            /** @var KpmVerification|null $verification */
            $verification = $participant
                ->activeVerification;

            $resolution = $this->resolution(
                $transaction,
                $verification
            );

            $this->incrementSnapshotSummary(
                $summary,
                $resolution
            );

            $this->incrementRegionSnapshot(
                $wilayah,
                $participant,
                $resolution
            );

            $this->incrementSurveyorSnapshot(
                $surveyors,
                $transaction,
                $verification
            );

            $this->incrementEWarungSnapshot(
                $eWarungs,
                $transaction
            );

            $details[] = $this->participantDetail(
                $participant,
                $transaction,
                $verification,
                $resolution
            );
        }

        $this->calculateCompletionPercentage(
            $summary
        );

        return $this->formatSnapshot(
            $summary,
            $wilayah,
            $surveyors,
            $eWarungs,
            $details
        );
    }

    private function snapshotParticipants(
        int $periodId
    ): \Illuminate\Support\Collection {
        return BpntParticipant::query()
            ->where('bpnt_period_id', $periodId)
            ->whereHas(
                'import',
                fn ($query) => $query->where(
                    'status',
                    BnbaImportStatus::CONFIRMED->value
                )
            )
            ->with([
                'kpm',
                'kelurahan.kecamatan',
                'transactions' => fn ($query) => $query
                    ->where('period_id', $periodId)
                    ->with([
                        'surveyor',
                        'eWarung',
                    ]),
                'activeVerification' => fn ($query) => $query
                    ->where('period_id', $periodId)
                    ->with('surveyor'),
            ])
            ->get()
            ->sortBy([
                ['kelurahan.kecamatan.name', 'asc'],
                ['kelurahan.name', 'asc'],
                ['kpm.full_name', 'asc'],
            ])
            ->values();
    }

    private function emptySnapshotSummary(
        int $totalKpm
    ): array {
        return [
            'total_kpm' => $totalKpm,
            'transacted' => 0,
            'pending' => 0,
            'active_verifications' => 0,
            'deceased' => 0,
            'moved_domicile' => 0,
            'not_claimed' => 0,
            'completion_percentage' => 0.0,
        ];
    }

    private function incrementSnapshotSummary(
        array &$summary,
        array $resolution
    ): void {
        $summaryKey = (string) $resolution['summary_key'];
        $summary[$summaryKey]++;

        if (
            $resolution['code'] !== 'pending'
            && $resolution['code'] !== 'transacted'
        ) {
            $summary['active_verifications']++;
        }
    }

    private function incrementRegionSnapshot(
        array &$regions,
        BpntParticipant $participant,
        array $resolution
    ): void {
        $kelurahanId = (int) $participant->kelurahan_id;

        if (! isset($regions[$kelurahanId])) {
            $regions[$kelurahanId] = $this->regionSnapshotRow(
                $participant
            );
        }

        $summaryKey = (string) $resolution['summary_key'];

        $regions[$kelurahanId]['total_kpm']++;
        $regions[$kelurahanId][$summaryKey]++;
    }

    private function regionSnapshotRow(
        BpntParticipant $participant
    ): array {
        $kelurahan = $participant->kelurahan;
        $kecamatan = $kelurahan?->kecamatan;

        return [
            'kecamatan' => [
                'id' => $kecamatan
                    ? (int) $kecamatan->id
                    : null,
                'name' => $kecamatan?->name,
            ],
            'kelurahan' => [
                'id' => $kelurahan
                    ? (int) $kelurahan->id
                    : null,
                'name' => $kelurahan?->name,
            ],
            'total_kpm' => 0,
            'transacted' => 0,
            'pending' => 0,
            'deceased' => 0,
            'moved_domicile' => 0,
            'not_claimed' => 0,
        ];
    }

    private function incrementSurveyorSnapshot(
        array &$surveyors,
        ?BpntTransaction $transaction,
        ?KpmVerification $verification
    ): void {
        $actor = $transaction?->surveyor
            ?? $verification?->surveyor;

        if ($actor === null) {
            return;
        }

        $actorId = (int) $actor->id;

        if (! isset($surveyors[$actorId])) {
            $surveyors[$actorId] = [
                'id' => $actorId,
                'name' => (string) $actor->name,
                'username' => (string) $actor->username,
                'assignment' => [
                    'kecamatan' => [
                        'id' => null,
                        'name' => null,
                    ],
                    'kelurahan' => [
                        'id' => null,
                        'name' => null,
                    ],
                ],
                'transactions' => 0,
                'verifications' => 0,
            ];
        }

        if ($transaction instanceof BpntTransaction) {
            $surveyors[$actorId]['transactions']++;
        }

        if ($verification instanceof KpmVerification) {
            $surveyors[$actorId]['verifications']++;
        }
    }

    private function incrementEWarungSnapshot(
        array &$eWarungs,
        ?BpntTransaction $transaction
    ): void {
        if (! $transaction instanceof BpntTransaction) {
            return;
        }

        $eWarungId = (int) $transaction->e_warung_id;

        if (! isset($eWarungs[$eWarungId])) {
            $eWarungs[$eWarungId] = [
                'id' => $eWarungId,
                'name' => (string) $transaction->eWarung?->name,
                'transactions' => 0,
            ];
        }

        $eWarungs[$eWarungId]['transactions']++;
    }

    private function calculateCompletionPercentage(
        array &$summary
    ): void {
        $resolved = (int) $summary['transacted']
            + (int) $summary['active_verifications'];

        $summary['completion_percentage'] =
            $summary['total_kpm'] > 0
                ? round(
                    ($resolved / $summary['total_kpm']) * 100,
                    2
                )
                : 0.0;
    }

    private function formatSnapshot(
        array $summary,
        array $regions,
        array $surveyors,
        array $eWarungs,
        array $details
    ): array {
        return [
            'generated_at' => now()
                ->timezone('Asia/Jakarta')
                ->toIso8601String(),
            'summary' => $summary,
            'wilayah' => collect($regions)
                ->sortBy([
                    ['kecamatan.name', 'asc'],
                    ['kelurahan.name', 'asc'],
                ])
                ->values()
                ->all(),
            'surveyors' => collect($surveyors)
                ->sortBy('name')
                ->values()
                ->all(),
            'e_warungs' => collect($eWarungs)
                ->sortByDesc('transactions')
                ->values()
                ->all(),
            'participants' => $details,
        ];
    }

    public function buildSummary(
        int $periodId
    ): array {
        $participantIds = BpntParticipant::query()
            ->select('id')
            ->where('bpnt_period_id', $periodId)
            ->whereHas(
                'import',
                fn ($query) => $query->where(
                    'status',
                    BnbaImportStatus::CONFIRMED->value
                )
            );

        $totalKpm = (clone $participantIds)->count();

        $transactionParticipantIds = BpntTransaction::query()
            ->select('bpnt_participant_id')
            ->where('period_id', $periodId);

        $transacted = BpntTransaction::query()
            ->where('period_id', $periodId)
            ->whereIn(
                'bpnt_participant_id',
                clone $participantIds
            )
            ->count();

        $verificationCounts = KpmVerification::query()
            ->where('period_id', $periodId)
            ->where('active_slot', 1)
            ->whereIn(
                'bpnt_participant_id',
                clone $participantIds
            )
            ->whereNotIn(
                'bpnt_participant_id',
                $transactionParticipantIds
            )
            ->selectRaw('status, COUNT(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status');

        $deceased = (int) $verificationCounts->get(
            KpmVerificationStatus::DECEASED->value,
            0
        );

        $movedDomicile = (int) $verificationCounts->get(
            KpmVerificationStatus::MOVED_DOMICILE->value,
            0
        );

        $notClaimed = (int) $verificationCounts->get(
            KpmVerificationStatus::NOT_CLAIMED->value,
            0
        );

        $activeVerifications = $deceased
            + $movedDomicile
            + $notClaimed;

        $resolved = $transacted
            + $activeVerifications;

        return [
            'total_kpm' => $totalKpm,
            'transacted' => $transacted,
            'pending' => max(
                $totalKpm - $resolved,
                0
            ),
            'active_verifications' => $activeVerifications,
            'deceased' => $deceased,
            'moved_domicile' => $movedDomicile,
            'not_claimed' => $notClaimed,
            'completion_percentage' => $totalKpm > 0
                ? round(
                    ($resolved / $totalKpm) * 100,
                    2
                )
                : 0.0,
        ];
    }

    private function initialSurveyors(
        int $periodId
    ): array {
        return SurveyorAssignment::query()
            ->where('period_id', $periodId)
            ->with([
                'surveyor',
                'kelurahan.kecamatan',
            ])
            ->get()
            ->mapWithKeys(
                function (SurveyorAssignment $assignment): array {
                    $surveyor = $assignment->surveyor;

                    return [
                        (int) $assignment->surveyor_id => [
                            'id' => (int) $assignment->surveyor_id,
                            'name' => (string) $surveyor->name,
                            'username' => (string) $surveyor->username,
                            'assignment' => [
                                'kecamatan' => [
                                    'id' => $assignment->kelurahan?->kecamatan
                                        ? (int) $assignment->kelurahan->kecamatan->id
                                        : null,
                                    'name' => $assignment->kelurahan?->kecamatan?->name,
                                ],
                                'kelurahan' => [
                                    'id' => $assignment->kelurahan
                                        ? (int) $assignment->kelurahan->id
                                        : null,
                                    'name' => $assignment->kelurahan?->name,
                                ],
                            ],
                            'transactions' => 0,
                            'verifications' => 0,
                        ],
                    ];
                }
            )
            ->all();
    }

    private function resolution(
        ?BpntTransaction $transaction,
        ?KpmVerification $verification
    ): array {
        if ($transaction instanceof BpntTransaction) {
            return [
                'code' => 'transacted',
                'label' => 'Sudah Transaksi',
                'summary_key' => 'transacted',
            ];
        }

        if ($verification instanceof KpmVerification) {
            /** @var KpmVerificationStatus $status */
            $status = $verification->status;

            return [
                'code' => $status->value,
                'label' => $status->label(),
                'summary_key' => $status->value,
            ];
        }

        return [
            'code' => 'pending',
            'label' => 'Belum Transaksi',
            'summary_key' => 'pending',
        ];
    }

    private function participantDetail(
        BpntParticipant $participant,
        ?BpntTransaction $transaction,
        ?KpmVerification $verification,
        array $resolution
    ): array {
        $actor = $transaction?->surveyor
            ?? $verification?->surveyor;

        $resolvedAt = $transaction?->transacted_at
            ?? $verification?->verified_at;

        return [
            'participant_id' => (int) $participant->id,
            'nik' => $this->identity->maskCiphertext(
                $participant->kpm->nik_ciphertext
            ),
            'full_name' => (string) $participant->kpm->full_name,
            'address' => (string) $participant->kpm->address,
            'rt' => $participant->kpm->rt,
            'rw' => $participant->kpm->rw,
            'wilayah' => [
                'kecamatan' => [
                    'id' => $participant->kelurahan?->kecamatan
                        ? (int) $participant->kelurahan->kecamatan->id
                        : null,
                    'name' => $participant->kelurahan?->kecamatan?->name,
                ],
                'kelurahan' => [
                    'id' => $participant->kelurahan
                        ? (int) $participant->kelurahan->id
                        : null,
                    'name' => $participant->kelurahan?->name,
                ],
            ],
            'resolution' => [
                'code' => $resolution['code'],
                'label' => $resolution['label'],
                'reason' => $verification?->reason,
            ],
            'surveyor' => $actor
                ? [
                    'id' => (int) $actor->id,
                    'name' => (string) $actor->name,
                ]
                : null,
            'e_warung' => $transaction?->eWarung
                ? [
                    'id' => (int) $transaction->eWarung->id,
                    'name' => (string) $transaction->eWarung->name,
                ]
                : null,
            'resolved_at' => $resolvedAt
                ?->timezone('Asia/Jakarta')
                ->toIso8601String(),
        ];
    }
}
