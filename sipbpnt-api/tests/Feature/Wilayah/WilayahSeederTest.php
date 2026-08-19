<?php

declare(strict_types=1);

namespace Tests\Feature\Wilayah;

use App\Models\Kecamatan;
use App\Models\Kelurahan;
use Database\Seeders\WilayahSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WilayahSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_wilayah_seeder_creates_three_kecamatans_and_eighteen_kelurahans(): void
    {
        $this->seed(
            WilayahSeeder::class
        );

        $this->assertDatabaseCount(
            'kecamatans',
            3
        );

        $this->assertDatabaseCount(
            'kelurahans',
            18
        );
    }

    public function test_each_kecamatan_has_six_kelurahans(): void
    {
        $this->seed(
            WilayahSeeder::class
        );

        $kecamatans =
            Kecamatan::query()
                ->withCount(
                    'kelurahans'
                )
                ->get();

        $this->assertCount(
            3,
            $kecamatans
        );

        foreach (
            $kecamatans
            as $kecamatan
        ) {
            $this->assertSame(
                6,
                $kecamatan
                    ->kelurahans_count
            );
        }
    }

    public function test_jagalan_belongs_to_kranggan(): void
    {
        $this->seed(
            WilayahSeeder::class
        );

        $jagalan =
            Kelurahan::query()
                ->with('kecamatan')
                ->where(
                    'name',
                    'Jagalan'
                )
                ->firstOrFail();

        $this->assertSame(
            'Kranggan',
            $jagalan
                ->kecamatan
                ->name
        );

        $this->assertSame(
            '35.76.03',
            $jagalan
                ->kecamatan
                ->code
        );
    }

    public function test_wilayah_seeder_is_idempotent(): void
    {
        $this->seed(
            WilayahSeeder::class
        );

        $this->seed(
            WilayahSeeder::class
        );

        $this->assertDatabaseCount(
            'kecamatans',
            3
        );

        $this->assertDatabaseCount(
            'kelurahans',
            18
        );
    }
}