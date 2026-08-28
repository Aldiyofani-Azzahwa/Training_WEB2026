<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Contracts\Repositories\BpntPeriodRepositoryInterface;
use App\Contracts\Repositories\BpntReportRepositoryInterface;
use App\Models\BpntPeriod;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\Response;

final class EnsureActivePeriodReportIsOpen
{
    public function __construct(
        private readonly BpntPeriodRepositoryInterface $periods,
        private readonly BpntReportRepositoryInterface $reports,
    ) {}

    public function handle(
        Request $request,
        Closure $next
    ): Response {
        return DB::transaction(
            function () use ($request, $next): Response {
                $activePeriod = $this->periods->active();

                if (! $activePeriod instanceof BpntPeriod) {
                    return $next($request);
                }

                $period = $this->periods->findForUpdate(
                    (int) $activePeriod->id
                );

                if (
                    ! $period->is_active
                    || (int) $period->active_slot !== 1
                ) {
                    throw ValidationException::withMessages([
                        'period' => [
                            'Periode BPNT aktif telah berubah. Silakan muat ulang halaman.',
                        ],
                    ]);
                }

                if (
                    $this->reports->isFinalForPeriod(
                        (int) $period->id
                    )
                ) {
                    throw ValidationException::withMessages([
                        'report' => [
                            'Laporan periode telah difinalkan. Data transaksi dan verifikasi tidak dapat diubah.',
                        ],
                    ]);
                }

                return $next($request);
            },
            3
        );
    }
}