<?php

namespace App\Http\Controllers;

use App\Services\ReportingService;
use Illuminate\Http\JsonResponse;

/**
 * HR reporting dashboard endpoints (FR-018, docs/API.md Section 7).
 *
 * HR-only access is enforced by the route middleware (auth:sanctum +
 * EnsureRole:hr_admin); this controller stays thin and delegates all
 * aggregation to ReportingService.
 */
class ReportController extends Controller
{
    public function __construct(
        private readonly ReportingService $reports,
    ) {}

    /**
     * GET /reports/overview — aggregate dashboard data.
     */
    public function overview(): JsonResponse
    {
        return response()->json($this->reports->overview());
    }

    /**
     * GET /reports/jobs/{id}/funnel — selection funnel for one job posting.
     */
    public function jobFunnel(string $id): JsonResponse
    {
        return response()->json($this->reports->jobFunnel($id));
    }
}
