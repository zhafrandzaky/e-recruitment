<?php

namespace App\Services;

use App\Models\JobPosting;
use Illuminate\Support\Facades\DB;

/**
 * Aggregate reporting queries for the HR dashboard (FR-018, Module 8).
 *
 * Every figure is computed by the database (COUNT/AVG/GROUP BY) — never by
 * loading rows into PHP and aggregating in application code (NFR-007). The
 * funnel queries lean on the composite index (job_posting_id, status) that
 * already exists on `applications` (docs/SCHEMA.md Section 4).
 *
 * All metrics operate on the visible dataset only: soft-deleted applications
 * and soft-deleted job postings are excluded everywhere, consistently.
 */
class ReportingService
{
    /**
     * The full aggregate dashboard payload (GET /reports/overview).
     *
     * @return array{
     *     applicants_per_job: list<array{job_id: string, job_title: string, count: int}>,
     *     funnel: array{pending: int, shortlisted: int, rejected: int, hired: int},
     *     avg_time_to_hire_days: float|null
     * }
     */
    public function overview(): array
    {
        return [
            'applicants_per_job' => $this->applicantsPerJob(),
            'funnel' => $this->funnel(),
            'avg_time_to_hire_days' => $this->averageTimeToHireDays(),
        ];
    }

    /**
     * Number of applications per job posting.
     *
     * LEFT JOIN so a posting with zero applicants still appears (count 0).
     * Soft-deleted applications are excluded via the join condition (kept out
     * of the ON clause's effect on the left side, so zeros are preserved).
     *
     * @return list<array{job_id: string, job_title: string, count: int}>
     */
    public function applicantsPerJob(): array
    {
        return DB::table('job_postings as jp')
            ->leftJoin('applications as a', function ($join) {
                $join->on('a.job_posting_id', '=', 'jp.id')
                    ->whereNull('a.deleted_at');
            })
            ->whereNull('jp.deleted_at')
            ->groupBy('jp.id', 'jp.title')
            ->select('jp.id as job_id', 'jp.title as job_title', DB::raw('COUNT(a.id) as applicant_count'))
            ->orderByDesc('applicant_count')
            ->orderBy('jp.title')
            ->get()
            ->map(fn ($row) => [
                'job_id' => $row->job_id,
                'job_title' => $row->job_title,
                'count' => (int) $row->applicant_count,
            ])
            ->all();
    }

    /**
     * Distribution of applications across the four status stages.
     *
     * When $jobId is given, scoped to that one posting (uses the composite
     * index). Missing statuses are filled with 0 so the shape is always complete.
     *
     * @return array{pending: int, shortlisted: int, rejected: int, hired: int}
     */
    public function funnel(?string $jobId = null): array
    {
        $query = DB::table('applications')->whereNull('deleted_at');

        if ($jobId !== null) {
            $query->where('job_posting_id', $jobId);
        }

        $counts = $query
            ->groupBy('status')
            ->select('status', DB::raw('COUNT(*) as total'))
            ->pluck('total', 'status');

        return [
            'pending' => (int) ($counts['pending'] ?? 0),
            'shortlisted' => (int) ($counts['shortlisted'] ?? 0),
            'rejected' => (int) ($counts['rejected'] ?? 0),
            'hired' => (int) ($counts['hired'] ?? 0),
        ];
    }

    /**
     * Selection funnel for a single job posting (GET /reports/jobs/{id}/funnel).
     *
     * @return array{
     *     job_id: string,
     *     job_title: string,
     *     funnel: array{pending: int, shortlisted: int, rejected: int, hired: int},
     *     total: int
     * }
     */
    public function jobFunnel(string $jobId): array
    {
        // findOrFail honours the SoftDeletes scope — a deleted/missing job → 404.
        $job = JobPosting::findOrFail($jobId);
        $funnel = $this->funnel($job->id);

        return [
            'job_id' => $job->id,
            'job_title' => $job->title,
            'funnel' => $funnel,
            'total' => array_sum($funnel),
        ];
    }

    /**
     * Average days from a job posting's creation to the moment an application
     * for it first reached 'hired' (FR-018 time-to-hire).
     *
     * Computed as a single AVG over a per-application "first hire" subquery, so
     * the database does all the work. The day-difference expression is
     * driver-aware (PostgreSQL interval vs. SQLite julianday) because tests run
     * on SQLite while production runs on PostgreSQL.
     *
     * Returns null when no application has been hired yet — a more honest signal
     * than 0 (which would read as "hired the same day").
     */
    public function averageTimeToHireDays(): ?float
    {
        $driver = DB::getDriverName();

        // Earliest 'hired' transition per application.
        $firstHire = DB::table('application_status_history')
            ->select('application_id', DB::raw('MIN(changed_at) as first_hired_at'))
            ->where('new_status', 'hired')
            ->groupBy('application_id');

        $dayDiff = $driver === 'pgsql'
            ? 'EXTRACT(EPOCH FROM (h.first_hired_at - jp.created_at)) / 86400.0'
            : '(julianday(h.first_hired_at) - julianday(jp.created_at))';

        $result = DB::query()
            ->fromSub($firstHire, 'h')
            ->join('applications as a', function ($join) {
                $join->on('a.id', '=', 'h.application_id')
                    ->whereNull('a.deleted_at');
            })
            ->join('job_postings as jp', function ($join) {
                $join->on('jp.id', '=', 'a.job_posting_id')
                    ->whereNull('jp.deleted_at');
            })
            ->selectRaw("AVG({$dayDiff}) as avg_days")
            ->first();

        return $result?->avg_days === null ? null : round((float) $result->avg_days, 1);
    }
}
