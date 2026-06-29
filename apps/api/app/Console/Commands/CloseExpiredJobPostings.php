<?php

namespace App\Console\Commands;

use App\Models\JobPosting;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;

class CloseExpiredJobPostings extends Command
{
    protected $signature = 'jobs:close-expired';

    protected $description = 'Close active job postings whose deadline has passed.';

    public function handle(): int
    {
        $count = JobPosting::where('status', 'active')
            ->whereNotNull('deadline')
            ->whereDate('deadline', '<', Carbon::today())
            ->update(['status' => 'closed']);

        $this->info("Closed {$count} expired job posting(s).");

        return Command::SUCCESS;
    }
}
