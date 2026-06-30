<?php

namespace Database\Seeders;

use App\Models\Application;
use App\Models\ApplicationStatusHistory;
use App\Models\ChatMessage;
use App\Models\ChatThread;
use App\Models\Interview;
use App\Models\JobPosting;
use App\Models\User;
use Faker\Factory as Faker;
use Faker\Generator;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

/**
 * Realistic demo data covering every entity (User, ApplicantProfile, JobPosting,
 * Application, ApplicationStatusHistory, Interview, ChatThread, ChatMessage).
 *
 * Intended for local/staging manual testing and demos. See docs/ENVIRONMENT.md
 * for the known test credentials and how to run it.
 *
 * Idempotency: designed for a FRESH/EMPTY database — run `php artisan migrate:fresh
 * --seed`. It is also safe to re-run by accident: if the demo HR account already
 * exists it skips entirely (no duplicate rows are appended). To re-seed from
 * scratch, run `migrate:fresh --seed` again.
 *
 * Never runs in production: guarded against `app()->environment('production')`.
 */
class DemoDataSeeder extends Seeder
{
    /** Shared password for all seeded accounts (documented in ENVIRONMENT.md). */
    private const DEMO_PASSWORD = 'password';

    private const HR_EMAIL = 'hr@example.com';

    public function run(): void
    {
        if (app()->environment('production')) {
            $this->command?->warn('DemoDataSeeder skipped: refusing to seed demo data in production.');

            return;
        }

        if (User::where('email', self::HR_EMAIL)->exists()) {
            $this->command?->info('DemoDataSeeder skipped: demo data already present (run migrate:fresh --seed to reset).');

            return;
        }

        $faker = Faker::create('id_ID');

        $hr = $this->createHr();
        $applicants = $this->createApplicants($faker);
        $jobs = $this->createJobs($hr);
        $applications = $this->createApplications($hr, $applicants, $jobs);
        $this->createInterviews($applications);
        $this->createChats($hr, $applications);

        $this->report($applications);
    }

    private function createHr(): User
    {
        return User::create([
            'name' => 'Sarah Wijaya',
            'email' => self::HR_EMAIL,
            'password' => self::DEMO_PASSWORD,
            'role' => 'hr_admin',
        ]);
    }

    /**
     * Known + curated applicants, each with an ApplicantProfile. The first three
     * use predictable emails for manual testing (see docs/ENVIRONMENT.md).
     *
     * @return Collection<int, User>
     */
    private function createApplicants(Generator $faker): Collection
    {
        // Known accounts with predictable emails (documented for manual testing).
        $known = [
            'pelamar1@example.com' => 'Budi Santoso',
            'pelamar2@example.com' => 'Siti Rahmawati',
            'pelamar3@example.com' => 'Ahmad Hidayat',
        ];

        // Curated additional applicants; emails derived from the name.
        $extra = [
            'Dewi Lestari', 'Rizki Pratama', 'Putri Anggraini', 'Eko Nugroho',
            'Maya Sari', 'Fajar Ramadhan', 'Nurul Aini', 'Andi Wijaya', 'Indah Permata',
        ];

        $roster = collect($known)
            ->map(fn (string $name, string $email) => ['name' => $name, 'email' => $email])
            ->values()
            ->concat(collect($extra)->map(fn (string $name) => [
                'name' => $name,
                'email' => Str::of($name)->lower()->replace(' ', '.').'@example.com',
            ]));

        return $roster->map(function (array $person) use ($faker) {
            $user = User::create([
                'name' => $person['name'],
                'email' => $person['email'],
                'password' => self::DEMO_PASSWORD,
                'role' => 'applicant',
            ]);

            $user->applicantProfile()->create([
                'phone' => '08'.$faker->numerify('##########'),
                'address' => $faker->streetAddress().', '.$faker->city().', '.$faker->state(),
            ]);

            return $user;
        });
    }

    /**
     * Five job postings (four active, one closed), created at staggered dates so
     * time-to-hire has a realistic spread.
     *
     * @return Collection<int, JobPosting>
     */
    private function createJobs(User $hr): Collection
    {
        $jobs = [
            [
                'title' => 'Backend Engineer (Laravel)',
                'location' => 'Jakarta Selatan, DKI Jakarta',
                'description' => 'Bertanggung jawab membangun dan memelihara REST API menggunakan Laravel, merancang skema basis data PostgreSQL, serta memastikan keandalan dan keamanan layanan backend.',
                'qualifications' => 'Minimal 2 tahun pengalaman Laravel; menguasai PostgreSQL dan konsep REST API; memahami pengujian otomatis (PHPUnit).',
                'status' => 'active',
                'created_days_ago' => 60,
                'deadline_days' => 14,
            ],
            [
                'title' => 'Frontend Developer (Vue.js)',
                'location' => 'Bandung, Jawa Barat',
                'description' => 'Mengembangkan antarmuka pengguna SPA dengan Vue 3 dan TypeScript, berkolaborasi dengan tim desain untuk mewujudkan pengalaman pengguna yang konsisten dan responsif.',
                'qualifications' => 'Menguasai Vue 3, TypeScript, dan Tailwind CSS; terbiasa dengan tooling modern (Vite); memahami prinsip aksesibilitas web.',
                'status' => 'active',
                'created_days_ago' => 45,
                'deadline_days' => 21,
            ],
            [
                'title' => 'UI/UX Designer',
                'location' => 'Remote (WFH)',
                'description' => 'Merancang alur pengguna, wireframe, dan prototipe interaktif untuk produk rekrutmen internal, serta menjaga konsistensi design system perusahaan.',
                'qualifications' => 'Portofolio desain produk digital; menguasai Figma; memahami riset pengguna dan pengujian kegunaan.',
                'status' => 'active',
                'created_days_ago' => 30,
                'deadline_days' => 18,
            ],
            [
                'title' => 'Data Analyst',
                'location' => 'Surabaya, Jawa Timur',
                'description' => 'Menganalisis data rekrutmen dan operasional, menyusun laporan berkala, serta menyajikan temuan yang dapat ditindaklanjuti kepada manajemen.',
                'qualifications' => 'Menguasai SQL dan spreadsheet tingkat lanjut; mampu memvisualisasikan data; teliti dan komunikatif.',
                'status' => 'active',
                'created_days_ago' => 20,
                'deadline_days' => 25,
            ],
            [
                'title' => 'HR Generalist',
                'location' => 'Yogyakarta, DI Yogyakarta',
                'description' => 'Menangani proses rekrutmen end-to-end, administrasi kepegawaian, dan pengembangan budaya kerja yang sehat.',
                'qualifications' => 'Minimal 3 tahun pengalaman di bidang HR; memahami ketenagakerjaan Indonesia; berorientasi pada manusia.',
                'status' => 'closed',
                'created_days_ago' => 75,
                'deadline_days' => -5,
            ],
        ];

        return collect($jobs)->map(function (array $j) use ($hr) {
            $createdAt = Carbon::now()->subDays($j['created_days_ago']);

            return JobPosting::create([
                'title' => $j['title'],
                'description' => $j['description'],
                'qualifications' => $j['qualifications'],
                'location' => $j['location'],
                'deadline' => $createdAt->copy()->addDays($j['created_days_ago'] + $j['deadline_days'])->toDateString(),
                'status' => $j['status'],
                'created_by' => $hr->id,
                'created_at' => $createdAt,
                'updated_at' => $createdAt,
            ]);
        });
    }

    /**
     * ~20 applications spread across jobs and statuses (incl. hired), each with a
     * consistent status-history timeline so reporting has real data.
     *
     * @param  Collection<int, User>  $applicants
     * @param  Collection<int, JobPosting>  $jobs
     * @return Collection<int, Application>
     */
    private function createApplications(User $hr, Collection $applicants, Collection $jobs): Collection
    {
        // [jobIndex, applicantIndex, status]
        $specs = [
            [0, 0, 'hired'], [0, 3, 'shortlisted'], [0, 4, 'rejected'], [0, 5, 'pending'],
            [1, 1, 'hired'], [1, 6, 'shortlisted'], [1, 7, 'pending'], [1, 8, 'pending'],
            [2, 11, 'hired'], [2, 2, 'shortlisted'], [2, 9, 'rejected'],
            [3, 10, 'shortlisted'], [3, 0, 'rejected'], [3, 3, 'pending'], [3, 11, 'pending'],
            [4, 5, 'hired'], [4, 6, 'shortlisted'], [4, 4, 'rejected'], [4, 8, 'rejected'], [4, 1, 'pending'],
        ];

        return collect($specs)->map(function (array $spec) use ($hr, $applicants, $jobs) {
            [$jobIndex, $applicantIndex, $status] = $spec;
            $job = $jobs[$jobIndex];
            $applicant = $applicants[$applicantIndex];

            return $this->makeApplication($hr, $applicant, $job, $status);
        });
    }

    private function makeApplication(User $hr, User $applicant, JobPosting $job, string $status): Application
    {
        $createdDaysAgo = Carbon::now()->diffInDays($job->created_at);
        $timeline = $this->timeline((int) $createdDaysAgo, $status);
        $appliedAt = Carbon::now()->subDays($timeline['applied']);

        $profile = $applicant->applicantProfile;

        $application = Application::create([
            'job_posting_id' => $job->id,
            'applicant_id' => $applicant->id,
            'cv_path' => 'applications/cv/'.Str::uuid().'.pdf', // placeholder — no real file is seeded to storage
            'cv_original_filename' => 'CV_'.Str::of($applicant->name)->replace(' ', '_').'.pdf',
            'additional_data' => [
                'name' => $applicant->name,
                'phone' => $profile?->phone,
                'address' => $profile?->address,
            ],
            'status' => $status,
            'applied_at' => $appliedAt,
            'created_at' => $appliedAt,
            'updated_at' => $appliedAt,
        ]);

        foreach ($timeline['transitions'] as $transition) {
            ApplicationStatusHistory::create([
                'application_id' => $application->id,
                'previous_status' => $transition['from'],
                'new_status' => $transition['to'],
                'changed_by' => $hr->id,
                'changed_at' => Carbon::now()->subDays($transition['days_ago']),
            ]);
        }

        return $application;
    }

    /**
     * A status-change timeline for one application, relative to how long ago its
     * job posting was created. Mirrors the real write path: no initial 'pending'
     * row is recorded (only HR-made transitions), and every changed_at sits
     * between the application date and now.
     *
     * @return array{applied: int, transitions: list<array{from: string, to: string, days_ago: int}>}
     */
    private function timeline(int $createdDaysAgo, string $status): array
    {
        $frac = fn (float $f) => max(2, (int) round($createdDaysAgo * $f));

        $applied = $frac(0.7);

        $transitions = match ($status) {
            'pending' => [],
            'shortlisted' => [
                ['from' => 'pending', 'to' => 'shortlisted', 'days_ago' => $frac(0.3)],
            ],
            'rejected' => [
                ['from' => 'pending', 'to' => 'rejected', 'days_ago' => $frac(0.35)],
            ],
            'hired' => [
                ['from' => 'pending', 'to' => 'shortlisted', 'days_ago' => $frac(0.45)],
                ['from' => 'shortlisted', 'to' => 'hired', 'days_ago' => $frac(0.2)],
            ],
            default => [],
        };

        return ['applied' => $applied, 'transitions' => $transitions];
    }

    /**
     * Five interviews with manually-entered meeting links (ADR-024 — no external
     * Calendar/Meet API). Completed for hired applicants, scheduled for an
     * upcoming shortlisted one.
     *
     * @param  Collection<int, Application>  $applications
     */
    private function createInterviews(Collection $applications): void
    {
        $links = [
            'https://meet.google.com/abc-defg-hij',
            'https://zoom.us/j/8475920183',
            'https://meet.google.com/qrs-tuvw-xyz',
            'https://teams.microsoft.com/l/meetup-join/demo-rekrutmen',
            'https://zoom.us/j/1029384756',
        ];

        $hired = $applications->where('status', 'hired')->values();
        $shortlisted = $applications->where('status', 'shortlisted')->values();
        $targets = $hired->concat($shortlisted->take(1))->values();

        foreach ($targets as $i => $application) {
            $isCompleted = $application->status === 'hired';

            Interview::create([
                'application_id' => $application->id,
                'scheduled_at' => $isCompleted
                    ? Carbon::now()->subDays(rand(5, 15))->setTime(10, 0)
                    : Carbon::now()->addDays(rand(2, 7))->setTime(14, 0),
                'meeting_link' => $links[$i % count($links)],
                'status' => $isCompleted ? 'completed' : 'scheduled',
            ]);
        }
    }

    /**
     * Six chat threads with a short Indonesian conversation each.
     *
     * @param  Collection<int, Application>  $applications
     */
    private function createChats(User $hr, Collection $applications): void
    {
        $script = [
            ['hr', 'Selamat siang, terima kasih sudah melamar di perusahaan kami. Apakah Anda bersedia mengikuti tahap interview?'],
            ['applicant', 'Selamat siang, terima kasih atas kesempatannya. Ya, saya bersedia.'],
            ['hr', 'Baik. Kami akan mengirimkan jadwal beserta tautan meeting melalui email Anda.'],
            ['applicant', 'Siap, saya tunggu informasinya. Terima kasih banyak.'],
            ['hr', 'Sama-sama. Jika ada kendala, silakan kabari kami melalui chat ini.'],
        ];

        foreach ($applications->take(6) as $application) {
            $thread = ChatThread::create([
                'application_id' => $application->id,
                'created_at' => $application->applied_at->copy()->addDays(1),
            ]);

            $messageCount = rand(2, 5);
            $base = $thread->created_at->copy();

            for ($i = 0; $i < $messageCount; $i++) {
                [$role, $content] = $script[$i];

                ChatMessage::create([
                    'chat_thread_id' => $thread->id,
                    'sender_id' => $role === 'hr' ? $hr->id : $application->applicant_id,
                    'content' => $content,
                    'sent_at' => $base->copy()->addMinutes($i * 7),
                ]);
            }
        }
    }

    private function report(Collection $applications): void
    {
        $byStatus = $applications->countBy('status');

        $this->command?->info('Demo data seeded:');
        $this->command?->line('  Users         : '.User::count().' ('.User::where('role', 'hr_admin')->count().' HR, '.User::where('role', 'applicant')->count().' applicants)');
        $this->command?->line('  Job postings  : '.JobPosting::count());
        $this->command?->line('  Applications  : '.$applications->count().
            ' (pending '.($byStatus['pending'] ?? 0).
            ', shortlisted '.($byStatus['shortlisted'] ?? 0).
            ', rejected '.($byStatus['rejected'] ?? 0).
            ', hired '.($byStatus['hired'] ?? 0).')');
        $this->command?->line('  Status history: '.ApplicationStatusHistory::count());
        $this->command?->line('  Interviews    : '.Interview::count());
        $this->command?->line('  Chat threads  : '.ChatThread::count().' / messages '.ChatMessage::count());
    }
}
