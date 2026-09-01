<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Models\BnbaImport;
use App\Models\BnbaImportRow;
use App\Models\BpntPeriod;
use App\Support\Security\SensitiveIdentity;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

class BnbaExportController extends Controller
{
    public function __construct(
        private readonly SensitiveIdentity $identity
    ) {}

    public function export(Request $request, int $periodId): StreamedResponse
    {
        $period = BpntPeriod::findOrFail($periodId);
        
        $import = BnbaImport::where('bpnt_period_id', $period->id)
            ->where('status', 'confirmed')
            ->firstOrFail();

        if (!\Illuminate\Support\Facades\Storage::disk('local')->exists($import->stored_path)) {
            abort(404, 'File BNBA asli tidak ditemukan di server.');
        }

        return \Illuminate\Support\Facades\Storage::disk('local')->download(
            $import->stored_path,
            $import->original_name
        );
    }
}
