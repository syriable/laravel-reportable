<?php

declare(strict_types=1);

namespace Syriable\Reportable\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Syriable\Reportable\Enums\ReportActionType;
use Syriable\Reportable\Models\Concerns\HasConfigurableIds;

/**
 * An immutable moderation action log entry.
 *
 * @property int|string $id
 * @property int|string $report_id
 * @property string $action
 * @property string|null $performed_by_type
 * @property int|string|null $performed_by_id
 * @property string|null $notes
 * @property array<array-key, mixed>|null $metadata
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property-read Report $report
 * @property-read \Illuminate\Database\Eloquent\Model|null $performer
 */
class ReportAction extends Model
{
    use HasConfigurableIds;

    public const UPDATED_AT = null;

    protected $guarded = [];

    protected $casts = [
        'metadata' => 'array',
    ];

    public function getTable(): string
    {
        return config('reportable.table_names.report_actions', parent::getTable());
    }

    public function report(): BelongsTo
    {
        /** @var class-string<Report> $reportModel */
        $reportModel = config('reportable.models.report', Report::class);

        return $this->belongsTo($reportModel, 'report_id');
    }

    public function performer(): MorphTo
    {
        return $this->morphTo('performer', 'performed_by_type', 'performed_by_id');
    }

    public function scopeForAction(Builder $query, ReportActionType|string $action): Builder
    {
        return $query->where(
            'action',
            $action instanceof ReportActionType ? $action->value : $action,
        );
    }
}
