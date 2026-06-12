<?php

declare(strict_types=1);

namespace Syriable\Reportable\Concerns;

use Illuminate\Database\Eloquent\Relations\MorphMany;
use Syriable\Reportable\Enums\ReportReason;
use Syriable\Reportable\Models\Report;

/**
 * Makes a model reportable.
 *
 * @mixin \Illuminate\Database\Eloquent\Model
 */
trait HasReports
{
    public function reports(): MorphMany
    {
        /** @var class-string<Report> $reportModel */
        $reportModel = config('reportable.models.report', Report::class);

        return $this->morphMany($reportModel, 'reportable');
    }

    public function activeReports(): MorphMany
    {
        return $this->reports()->active();
    }

    public function hasActiveReports(): bool
    {
        return $this->activeReports()->exists();
    }

    public function reportCount(): int
    {
        return $this->reports()->count();
    }

    public function activeReportCount(): int
    {
        return $this->activeReports()->count();
    }

    public function reportsForReason(ReportReason|string $reason): MorphMany
    {
        return $this->reports()->forReason($reason);
    }
}
