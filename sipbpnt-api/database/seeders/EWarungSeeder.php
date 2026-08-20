<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\EWarung;
use Illuminate\Database\Seeder;

class EWarungSeeder extends Seeder
{
    public function run(): void
    {
        $names = [
            'E-WAROENG ANGGREK SURODINAWAN',
            'E-WAROENG BANCANG SEJAHTERA WATES',
            'E-WAROENG FITRAH SEJAHTERA WATES',
            'E-WAROENG FLAMBOYAN KRANGGAN',
            'E-WAROENG I MERI',
            'E-WAROENG MAKMUR CERIA KEDUNDUNG',
            'E-WAROENG MELATI PRAJURITKULON',
            'E-WAROENG MIJI ABADI',
            'E-WAROENG PURWOTENGAH JAYA PURWOTENGAH',
            'E-WAROENG INDAH GUNUNGGEDANGAN',
            'E-WAROENG SETIA KEDUNDUNG',
            'E-WAROENG SIDOMULYO MENTIKAN',
            'E-WAROENG TERATAI PULOREJO',
        ];

        foreach ($names as $name) {
            EWarung::query()
                ->firstOrCreate(
                    [
                        'name' => $name,
                    ],
                    [
                        'is_active' => true,
                    ]
                );
        }
    }
}