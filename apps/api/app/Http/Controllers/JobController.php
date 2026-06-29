<?php

namespace App\Http\Controllers;

use App\Models\JobPosting;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Symfony\Component\HttpFoundation\Response;

class JobController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = JobPosting::active();

        if ($search = $request->query('search')) {
            $query->search($search);
        }

        $jobs = $query->orderByDesc('created_at')
            ->paginate($request->integer('per_page', 15));

        return response()->json([
            'data' => $jobs->map(fn (JobPosting $job) => [
                'id' => $job->id,
                'title' => $job->title,
                'location' => $job->location,
                'deadline' => $job->deadline?->toDateString(),
                'status' => $job->status,
                'created_at' => $job->created_at->toIso8601String(),
            ]),
            'meta' => [
                'page' => $jobs->currentPage(),
                'per_page' => $jobs->perPage(),
                'total' => $jobs->total(),
            ],
        ]);
    }

    public function show(string $id): JsonResponse
    {
        $job = JobPosting::active()->findOrFail($id);

        return response()->json([
            'id' => $job->id,
            'title' => $job->title,
            'description' => $job->description,
            'qualifications' => $job->qualifications,
            'location' => $job->location,
            'deadline' => $job->deadline?->toDateString(),
            'status' => $job->status,
            'created_at' => $job->created_at->toIso8601String(),
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'description' => ['required', 'string'],
            'qualifications' => ['required', 'string'],
            'location' => ['required', 'string', 'max:255'],
            'deadline' => ['required', 'date', 'after:today'],
            'status' => ['sometimes', Rule::in(['draft', 'active'])],
        ]);

        $job = JobPosting::create([
            ...$validated,
            'created_by' => $request->user()->id,
            'status' => $validated['status'] ?? 'draft',
        ]);

        return response()->json($this->formatJob($job), Response::HTTP_CREATED);
    }

    public function update(Request $request, string $id): JsonResponse
    {
        $job = JobPosting::findOrFail($id);

        $validated = $request->validate([
            'title' => ['sometimes', 'required', 'string', 'max:255'],
            'description' => ['sometimes', 'required', 'string'],
            'qualifications' => ['sometimes', 'required', 'string'],
            'location' => ['sometimes', 'required', 'string', 'max:255'],
            'deadline' => ['sometimes', 'required', 'date', 'after:today'],
            'status' => ['sometimes', Rule::in(['draft', 'active', 'closed'])],
        ]);

        $job->update($validated);

        return response()->json($this->formatJob($job->fresh()));
    }

    public function updateStatus(Request $request, string $id): JsonResponse
    {
        $job = JobPosting::findOrFail($id);

        $validated = $request->validate([
            'status' => ['required', Rule::in(['draft', 'active', 'closed'])],
        ]);

        $job->update(['status' => $validated['status']]);

        return response()->json([
            'id' => $job->id,
            'status' => $job->fresh()->status,
            'updated_at' => $job->fresh()->updated_at->toIso8601String(),
        ]);
    }

    public function destroy(string $id): JsonResponse
    {
        $job = JobPosting::findOrFail($id);
        $job->delete();

        return response()->json(null, Response::HTTP_NO_CONTENT);
    }

    private function formatJob(JobPosting $job): array
    {
        return [
            'id' => $job->id,
            'title' => $job->title,
            'description' => $job->description,
            'qualifications' => $job->qualifications,
            'location' => $job->location,
            'deadline' => $job->deadline?->toDateString(),
            'status' => $job->status,
            'created_by' => $job->created_by,
            'created_at' => $job->created_at->toIso8601String(),
            'updated_at' => $job->updated_at->toIso8601String(),
        ];
    }
}
