<?php

namespace App\Http\Controllers;

use App\Models\JobPosting;
use App\Models\User;
use Illuminate\Http\JsonResponse;

class PublicController extends Controller
{
    public function stats(): JsonResponse
    {
        return response()->json([
            'active_jobs' => JobPosting::where('status', 'active')->count(),
            'registered_applicants' => User::where('role', 'applicant')->count(),
        ]);
    }
}
