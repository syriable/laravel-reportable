<?php

declare(strict_types=1);

namespace Syriable\Reportable\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Syriable\Reportable\Enums\ReportReason;
use Syriable\Reportable\Enums\ReportStatus;
use Syriable\Reportable\Events\ReportUpdated;
use Syriable\Reportable\Models\Concerns\HasConfigurableIds;
use Syriable\Reportable\Support\ReasonRegistry;

/**
 * @property int|string $id
 * @property string $reporter_type
 * @property int|string $reporter_id
 * @property string $reportable_type
 * @property int|string $reportable_id
 * @property ReportStatus $status
 * @property string $reason
 * @property array<array-key, mixed>|null $metadata
 * @property string|null $internal_notes
 * @property string|null $reviewed_by_type
 * @property int|string|null $reviewed_by_id
 * @property \Illuminate\Support\Carbon|null $reviewed_at
 * @property \Illuminate\Support\Carbon|null $resolved_at
 * @property \Illuminate\Support\Carbon|null $closed_at
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property \Illuminate\Support\Carbon|null $deleted_at
 * @property-read \Illuminate\Database\Eloquent\Model $reportable
 * @property-read \Illuminate\Database\Eloquent\Model $reporter
 * @property-read \Illuminate\Database\Eloquent\Model|null $reviewer
 * @property-read \Illuminate\Database\Eloquent\Collection<int, ReportAction> $actions
 */
class Report extends Model
{
    use HasConfigurableIds;
    use SoftDeletes;

    protected $guarded = [];

    protected $casts = [
        'status' => ReportStatus::class,
        'metadata' => 'array',
        'reviewed_at' => 'datetime',
        'resolved_at' => 'datetime',
        'closed_at' => 'datetime',
    ];

    protected $dispatchesEvents = [
        'updated' => ReportUpdated::class,
    ];

    public function getTable(): string
    {
        return config('reportable.table_names.reports', parent::getTable());
    }

    /*
    |--------------------------------------------------------------------------
    | Relationships
    |--------------------------------------------------------------------------
    */

    public function reportable(): MorphTo
    {
        return $this->morphTo('reportable');
    }

    public function reporter(): MorphTo
    {
        return $this->morphTo('reporter');
    }

    public function reviewer(): MorphTo
    {
        return $this->morphTo('reviewer', 'reviewed_by_type', 'reviewed_by_id');
    }

    public function actions(): HasMany
    {
        /** @var class-string<ReportAction> $actionModel */
        $actionModel = config('reportable.models.report_action', ReportAction::class);

        return $this->hasMany($actionModel, 'report_id');
    }

    /*
    |--------------------------------------------------------------------------
    | Scopes
    |--------------------------------------------------------------------------
    */

    public function scopePending(Builder $query): Builder
    {
        return $query->where('status', ReportStatus::Pending->value);
    }

    public function scopeUnderReview(Builder $query): Builder
    {
        return $query->where('status', ReportStatus::UnderReview->value);
    }

    public function scopeResolved(Builder $query): Builder
    {
        return $query->where('status', ReportStatus::Resolved->value);
    }

    public function scopeRejected(Builder $query): Builder
    {
        return $query->where('status', ReportStatus::Rejected->value);
    }

    public function scopeClosed(Builder $query): Builder
    {
        return $query->where('status', ReportStatus::Closed->value);
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->whereIn('status', ReportStatus::activeValues());
    }

    public function scopeTerminal(Builder $query): Builder
    {
        return $query->whereIn('status', ReportStatus::terminalValues());
    }

    public function scopeForReason(Builder $query, ReportReason|string $reason): Builder
    {
        return $query->where('reason', ReasonRegistry::normalize($reason));
    }

    public function scopeForReportable(Builder $query, Model $reportable): Builder
    {
        return $query->whereMorphedTo('reportable', $reportable);
    }

    public function scopeByReporter(Builder $query, Model $reporter): Builder
    {
        return $query->whereMorphedTo('reporter', $reporter);
    }

    /*
    |--------------------------------------------------------------------------
    | Helpers
    |--------------------------------------------------------------------------
    */

    public function isActive(): bool
    {
        return $this->status->isActive();
    }

    public function isTerminal(): bool
    {
        return $this->status->isTerminal();
    }

    public function reasonEnum(): ?ReportReason
    {
        return ReportReason::tryFrom($this->reason);
    }
}
