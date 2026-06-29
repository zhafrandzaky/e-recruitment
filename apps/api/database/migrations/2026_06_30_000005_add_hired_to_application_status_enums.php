<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Add 'hired' to the application status enums (Phase 5 — ADR-026).
     *
     * The reporting time-to-hire metric (FR-018) is defined as the time until an
     * applicant reaches a final *accepted* state, but the original enum only had
     * pending/shortlisted/rejected — no terminal "accepted" status. 'hired'
     * (label "Diterima") is added so the metric is computed from a real status,
     * not a proxy. Affects:
     *   - applications.status
     *   - application_status_history.previous_status
     *   - application_status_history.new_status
     *
     * Laravel stores enum() as a varchar + named CHECK constraint on both
     * PostgreSQL (production) and SQLite (tests), so this is driver-aware:
     *   - pgsql: drop + recreate the named CHECK constraints (no table rebuild).
     *   - sqlite/other: redefine the columns via ->change() (table rebuild,
     *     indexes preserved).
     */
    private const STATUSES = ['pending', 'shortlisted', 'rejected', 'hired'];

    private const STATUSES_BEFORE = ['pending', 'shortlisted', 'rejected'];

    public function up(): void
    {
        $this->applyStatuses(self::STATUSES);
    }

    public function down(): void
    {
        // Reverts to the 3-value set. Will fail loudly (constraint violation) if
        // any 'hired' rows still exist — intentional: data must be migrated first.
        $this->applyStatuses(self::STATUSES_BEFORE);
    }

    /**
     * @param  list<string>  $statuses
     */
    private function applyStatuses(array $statuses): void
    {
        if (DB::getDriverName() === 'pgsql') {
            $this->applyPostgresChecks($statuses);

            return;
        }

        $this->applyViaSchemaChange($statuses);
    }

    /**
     * @param  list<string>  $statuses
     */
    private function applyPostgresChecks(array $statuses): void
    {
        $list = collect($statuses)->map(fn (string $s) => "'{$s}'")->implode(', ');

        $checks = [
            'applications' => ['applications_status_check' => 'status'],
            'application_status_history' => [
                'application_status_history_previous_status_check' => 'previous_status',
                'application_status_history_new_status_check' => 'new_status',
            ],
        ];

        foreach ($checks as $table => $constraints) {
            foreach ($constraints as $constraint => $column) {
                DB::statement("ALTER TABLE {$table} DROP CONSTRAINT {$constraint}");
                DB::statement("ALTER TABLE {$table} ADD CONSTRAINT {$constraint} CHECK ({$column} IN ({$list}))");
            }
        }
    }

    /**
     * @param  list<string>  $statuses
     */
    private function applyViaSchemaChange(array $statuses): void
    {
        Schema::table('applications', function (Blueprint $table) use ($statuses) {
            $table->enum('status', $statuses)->default('pending')->change();
        });

        Schema::table('application_status_history', function (Blueprint $table) use ($statuses) {
            $table->enum('previous_status', $statuses)->nullable()->change();
            $table->enum('new_status', $statuses)->change();
        });
    }
};
