<?php

declare(strict_types=1);

namespace Tests\Feature\Bnba;

use App\Contracts\Repositories\BnbaImportRepositoryInterface;
use App\Enums\BnbaImportStatus;
use App\Enums\UserRole;
use App\Models\BnbaImport;
use App\Models\BpntPeriod;
use App\Models\Kelurahan;
use App\Models\SurveyorAssignment;
use App\Models\User;
use App\Repositories\EloquentBnbaImportRepository;
use App\Services\Bnba\BnbaPeriodDeletionService;
use Database\Seeders\WilayahSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Mockery;
use RuntimeException;
use Tests\TestCase;

class BnbaPeriodDeletionAtomicityTest
    extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('local');

        $this->seed(
            WilayahSeeder::class
        );
    }

    public function test_deleting_bnba_also_deletes_old_assignments(): void
    {
        [
            'admin' => $admin,
            'period' => $period,
            'assignment' => $assignment,
            'import' => $import,
        ] =
            $this->createDeletionFixture();

        $storedPath =
            $import->stored_path;

        $this->assertLocalFileExists(
            $storedPath
        );

        $result =
            $this->app
                ->make(
                    BnbaPeriodDeletionService::class
                )
                ->deleteForPeriod(
                    $period->id,
                    $admin,
                    '127.0.0.1',
                    'PHPUnit'
                );

        $this->assertSame(
            1,
            $result[
                'imports_deleted'
            ]
        );

        $this->assertSame(
            0,
            $result[
                'participants_deleted'
            ]
        );

        $this->assertSame(
            1,
            $result[
                'assignments_deleted'
            ]
        );

        $this->assertDatabaseMissing(
            'surveyor_assignments',
            [
                'id'
                    => $assignment->id,
            ]
        );

        $this->assertDatabaseMissing(
            'bnba_imports',
            [
                'id'
                    => $import->id,
            ]
        );

        $this->assertDatabaseHas(
            'audit_logs',
            [
                'action'
                    => 'surveyor_assignment.deleted_with_bnba',

                'auditable_id'
                    => $assignment->id,
            ]
        );

        $this->assertDatabaseHas(
            'audit_logs',
            [
                'action'
                    => 'bnba.period_data.deleted',

                'auditable_id'
                    => $period->id,
            ]
        );

        $this->assertLocalFileMissing(
            $storedPath
        );
    }

    public function test_assignment_and_audit_are_rolled_back_when_bnba_deletion_fails(): void
    {
        [
            'admin' => $admin,
            'period' => $period,
            'assignment' => $assignment,
            'import' => $import,
        ] =
            $this->createDeletionFixture();

        $storedPath =
            $import->stored_path;

        /*
         * Repository asli tetap digunakan untuk
         * mengambil dan mengunci import.
         */
        $realImportRepository =
            new EloquentBnbaImportRepository();

        /*
         * Simulasikan kegagalan database tepat
         * ketika import akan dihapus.
         */
        $failingImportRepository =
            Mockery::mock(
                BnbaImportRepositoryInterface::class
            );

        $failingImportRepository
            ->shouldReceive(
                'forPeriodForUpdate'
            )
            ->once()
            ->with(
                $period->id
            )
            ->andReturnUsing(
                static fn (int $periodId) =>
                    $realImportRepository
                        ->forPeriodForUpdate(
                            $periodId
                        )
            );

        $failingImportRepository
            ->shouldReceive(
                'deleteForPeriod'
            )
            ->once()
            ->with(
                $period->id
            )
            ->andThrow(
                new RuntimeException(
                    'Simulasi kegagalan penghapusan BNBA.'
                )
            );

        $this->app
            ->instance(
                BnbaImportRepositoryInterface::class,
                $failingImportRepository
            );

        $service =
            $this->app
                ->make(
                    BnbaPeriodDeletionService::class
                );

        try {
            $service
                ->deleteForPeriod(
                    $period->id,
                    $admin,
                    '127.0.0.1',
                    'PHPUnit'
                );

            $this->fail(
                'RuntimeException seharusnya dilempar.'
            );
        } catch (
            RuntimeException $exception
        ) {
            $this->assertSame(
                'Simulasi kegagalan penghapusan BNBA.',
                $exception->getMessage()
            );
        }

        /*
         * Assignment wajib kembali karena seluruh
         * operasi berada dalam satu transaction.
         */
        $this->assertDatabaseHas(
            'surveyor_assignments',
            [
                'id'
                    => $assignment->id,

                'period_id'
                    => $period->id,
            ]
        );

        /*
         * Import juga harus tetap tersedia.
         */
        $this->assertDatabaseHas(
            'bnba_imports',
            [
                'id'
                    => $import->id,

                'bpnt_period_id'
                    => $period->id,
            ]
        );

        /*
         * Audit yang dibuat sebelum exception
         * juga wajib ikut rollback.
         */
        $this->assertDatabaseMissing(
            'audit_logs',
            [
                'action'
                    => 'surveyor_assignment.deleted_with_bnba',

                'auditable_id'
                    => $assignment->id,
            ]
        );

        $this->assertDatabaseMissing(
            'audit_logs',
            [
                'action'
                    => 'bnba.period_data.deleted',

                'auditable_id'
                    => $period->id,
            ]
        );

        /*
         * File tidak boleh dihapus karena
         * database transaction gagal.
         */
        $this->assertLocalFileExists(
            $storedPath
        );
    }

    public function test_bnba_from_active_period_cannot_be_deleted(): void
    {
        [
            'admin' => $admin,
            'period' => $period,
            'assignment' => $assignment,
            'import' => $import,
        ] =
            $this->createDeletionFixture(
                active: true
            );

        $storedPath =
            $import->stored_path;

        try {
            $this->app
                ->make(
                    BnbaPeriodDeletionService::class
                )
                ->deleteForPeriod(
                    $period->id,
                    $admin,
                    '127.0.0.1',
                    'PHPUnit'
                );

            $this->fail(
                'ValidationException seharusnya dilempar.'
            );
        } catch (
            ValidationException $exception
        ) {
            $this->assertSame(
                'BNBA pada periode aktif tidak dapat dihapus. Nonaktifkan periode terlebih dahulu.',
                $exception
                    ->errors()[
                        'period'
                    ][0]
            );
        }

        $this->assertDatabaseHas(
            'surveyor_assignments',
            [
                'id'
                    => $assignment->id,
            ]
        );

        $this->assertDatabaseHas(
            'bnba_imports',
            [
                'id'
                    => $import->id,
            ]
        );

        $this->assertLocalFileExists(
            $storedPath
        );
    }

    /**
     * @return array{
     *     admin: User,
     *     period: BpntPeriod,
     *     assignment: SurveyorAssignment,
     *     import: BnbaImport
     * }
     */
    private function createDeletionFixture(
        bool $active = false
    ): array {
        $admin =
            User::factory()
                ->create([
                    'role'
                        => UserRole::ADMIN_DINSOS,

                    'is_active'
                        => true,
                ]);

        $surveyor =
            User::factory()
                ->create([
                    'role'
                        => UserRole::SURVEYOR,

                    'is_active'
                        => true,
                ]);

        $period =
            BpntPeriod::query()
                ->create([
                    'code'
                        => $active
                            ? 'BPNT-2026-ACTIVE-DELETE'
                            : 'BPNT-2026-ATOMIC-DELETE',

                    'name'
                        => $active
                            ? 'Periode Aktif Delete'
                            : 'Periode Atomic Delete',

                    'year'
                        => 2026,

                    'is_active'
                        => $active,

                    'active_slot'
                        => $active
                            ? 1
                            : null,
                ]);

        $kelurahan =
            Kelurahan::query()
                ->where(
                    'name',
                    'Jagalan'
                )
                ->firstOrFail();

        $assignment =
            SurveyorAssignment::query()
                ->create([
                    'period_id'
                        => $period->id,

                    'surveyor_id'
                        => $surveyor->id,

                    'kelurahan_id'
                        => $kelurahan->id,

                    'assigned_by'
                        => $admin->id,

                    'assigned_at'
                        => now(),
                ]);

        $storedPath =
            sprintf(
                'bnba-imports/tests/period-%d.xlsx',
                $period->id
            );

        Storage::disk('local')
            ->put(
                $storedPath,
                'dummy-bnba-file'
            );

        $import =
            BnbaImport::query()
                ->create([
                    'bpnt_period_id'
                        => $period->id,

                    'uploaded_by'
                        => $admin->id,

                    'status'
                        => BnbaImportStatus::PREVIEW_READY,

                    'original_name'
                        => 'bnba-test.xlsx',

                    'stored_path'
                        => $storedPath,

                    'file_sha256'
                        => hash(
                            'sha256',
                            'dummy-bnba-file'
                        ),

                    'total_rows'
                        => 0,

                    'valid_rows'
                        => 0,

                    'warning_rows'
                        => 0,

                    'invalid_rows'
                        => 0,

                    'duplicate_rows'
                        => 0,
                ]);

        return [
            'admin'
                => $admin,

            'period'
                => $period,

            'assignment'
                => $assignment,

            'import'
                => $import,
        ];
    }

    private function assertLocalFileExists(
        string $path
    ): void {
        $this->assertTrue(
            Storage::disk('local')
                ->exists(
                    $path
                ),
            sprintf(
                'File [%s] seharusnya tersedia pada storage local.',
                $path
            )
        );
    }

    private function assertLocalFileMissing(
        string $path
    ): void {
        $this->assertFalse(
            Storage::disk('local')
                ->exists(
                    $path
                ),
            sprintf(
                'File [%s] seharusnya sudah tidak tersedia pada storage local.',
                $path
            )
        );
    }
}