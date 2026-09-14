<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Enums\PermissionEnum;
use App\Http\Controllers\Controller;
use App\Services\ReportService;
use Illuminate\Http\JsonResponse;

class ReportController extends Controller
{
    public function __construct(private readonly ReportService $reportService)
    {
        $this->middleware('permission:'.PermissionEnum::REPORTS_VIEW->value);
    }

    public function dashboard(): JsonResponse
    {
        return response()->json(['data' => $this->reportService->dashboard()]);
    }
}
