<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable(['job_posting_id', 'applicant_id', 'cv_path', 'cv_original_filename', 'additional_data', 'status', 'applied_at'])]
class Application extends Model
{
    use HasFactory, HasUuids, SoftDeletes;

    protected function casts(): array
    {
        return [
            'additional_data' => 'array',
            'applied_at' => 'datetime',
        ];
    }

    /**
     * The job posting this application is for.
     */
    public function jobPosting(): BelongsTo
    {
        return $this->belongsTo(JobPosting::class, 'job_posting_id');
    }

    /**
     * The applicant (user with role='applicant') who submitted this.
     */
    public function applicant(): BelongsTo
    {
        return $this->belongsTo(User::class, 'applicant_id');
    }

    /**
     * Status change history for reporting/audit.
     */
    public function statusHistory(): HasMany
    {
        return $this->hasMany(ApplicationStatusHistory::class, 'application_id')->orderBy('changed_at');
    }

    /**
     * The one-to-one real-time chat thread for this application (FR-017).
     * Created lazily on first use — may be absent.
     */
    public function chatThread(): HasOne
    {
        return $this->hasOne(ChatThread::class, 'application_id');
    }

    /**
     * Whether the given user may read/write this application's chat thread.
     *
     * The owning applicant or any HR admin may participate (single HR role —
     * ADR-006). This single predicate is the source of truth reused by both
     * the REST endpoints (ChatController) and the WebSocket channel
     * authorization (routes/channels.php), per docs/SECURITY.md Section 3.2.
     */
    public function canAccessChat(User $user): bool
    {
        return $user->isHrAdmin() || $user->id === $this->applicant_id;
    }
}
