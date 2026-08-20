<?php

declare(strict_types=1);

namespace App\Enums;

enum UserRole: string
{
    case ADMIN_DINSOS = 'admin_dinsos';
    case MANAGER = 'manager';
    case SURVEYOR = 'surveyor';
    case KEPALA_DINAS = 'kepala_dinas';

    public function label(): string
    {
        return match ($this) {
            self::ADMIN_DINSOS
                => 'Admin Dinsos',

            self::MANAGER
                => 'Manager',

            self::SURVEYOR
                => 'Surveyor',

            self::KEPALA_DINAS
                => 'Kepala Dinas',
        };
    }

    /**
     * Daftar modul utama
     * yang dapat diakses pengguna.
     *
     * @return array<int, string>
     */
    public function modules(): array
    {
        return match ($this) {
            self::ADMIN_DINSOS => [
                'Dashboard',
                'Periode BPNT',
                'Import BNBA',
                'Data KPM',
                'Akun Surveyor',
                'E-Warung',
                'Rekapitulasi',
                'Laporan',
            ],

            self::MANAGER => [
                'Dashboard',
                'Penugasan Surveyor',
                'Monitoring Transaksi',
                'Validasi Periode',
                'Laporan',
            ],

            self::SURVEYOR => [
                'Dashboard',
                'Pencarian KPM',
                'Transaksi',
                'Pendataan KPM',
                'Riwayat',
            ],

            self::KEPALA_DINAS => [
                'Dashboard',
                'Monitoring',
                'Rekapitulasi',
                'Laporan',
            ],
        };
    }
}