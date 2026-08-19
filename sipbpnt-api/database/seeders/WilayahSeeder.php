<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Kecamatan;
use App\Models\Kelurahan;
use Illuminate\Database\Seeder;

class WilayahSeeder extends Seeder
{
    public function run(): void
    {
        $wilayah = [
            [
                'code' => '35.76.01',
                'name' => 'Prajurit Kulon',

                'kelurahans' => [
                    [
                        'code' => '1003',
                        'name' => 'Mentikan',
                    ],
                    [
                        'code' => '1004',
                        'name' => 'Kauman',
                    ],
                    [
                        'code' => '1005',
                        'name' => 'Pulorejo',
                    ],
                    [
                        'code' => '1006',
                        'name' => 'Prajurit Kulon',
                    ],
                    [
                        'code' => '1007',
                        'name' => 'Surodinawan',
                    ],
                    [
                        'code' => '1008',
                        'name' => 'Blooto',
                    ],
                ],
            ],

            [
                'code' => '35.76.02',
                'name' => 'Magersari',

                'kelurahans' => [
                    [
                        'code' => '1001',
                        'name' => 'Gununggedangan',
                    ],
                    [
                        'code' => '1003',
                        'name' => 'Magersari',
                    ],
                    [
                        'code' => '1004',
                        'name' => 'Gedongan',
                    ],
                    [
                        'code' => '1008',
                        'name' => 'Balongsari',
                    ],
                    [
                        'code' => '1009',
                        'name' => 'Kedundung',
                    ],
                    [
                        'code' => '1010',
                        'name' => 'Wates',
                    ],
                ],
            ],

            [
                'code' => '35.76.03',
                'name' => 'Kranggan',

                'kelurahans' => [
                    [
                        'code' => '1001',
                        'name' => 'Kranggan',
                    ],
                    [
                        'code' => '1002',
                        'name' => 'Miji',
                    ],
                    [
                        'code' => '1003',
                        'name' => 'Meri',
                    ],
                    [
                        'code' => '1004',
                        'name' => 'Jagalan',
                    ],
                    [
                        'code' => '1005',
                        'name' => 'Sentanan',
                    ],
                    [
                        'code' => '1006',
                        'name' => 'Purwotengah',
                    ],
                ],
            ],
        ];

        foreach (
            $wilayah
            as $kecamatanData
        ) {
            $kecamatan =
                Kecamatan::query()
                    ->updateOrCreate(
                        [
                            'code'
                                => $kecamatanData[
                                    'code'
                                ],
                        ],
                        [
                            'name'
                                => $kecamatanData[
                                    'name'
                                ],
                        ]
                    );

            foreach (
                $kecamatanData[
                    'kelurahans'
                ]
                as $kelurahanData
            ) {
                Kelurahan::query()
                    ->updateOrCreate(
                        [
                            'kecamatan_id'
                                => $kecamatan->id,

                            'code'
                                => $kelurahanData[
                                    'code'
                                ],
                        ],
                        [
                            'name'
                                => $kelurahanData[
                                    'name'
                                ],
                        ]
                    );
            }
        }
    }
}