<?php

declare(strict_types=1);

namespace Syriable\Reportable\Concerns;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Syriable\Reportable\Enums\ReportReason;
use Syriable\Reportable\Models\Report;
use Syriable\Reportable\Services\ReportService;

/**
 * Allows a model (user, admin, moderator, ...) to create reports.
 *
 * @mixin \Illuminate\Database\Eloquent\Model
 */
trait CanReport
{
    /**
     * Report the given model.
     *
     * @param  array<array-key, mixed>  $metadata
     *
     * @throws \Syriable\Reportable\Exceptions\DuplicateReportException
     * @throws \Syriable\Reportable\Exceptions\ReportCooldownException
     * @throws \Syriable\Reportable\Exceptions\InvalidReportReasonException
     */
    public function report(Model $reportable, ReportReason|string $reason, array $metadata = []): Report
    {
        return app(ReportService::class)->report($this, $reportable, $reason, $metadata);
    }

    public function canReport(Model $reportable): bool
    {
        return app(ReportService::class)->canReport($this, $reportable);
    }

    public function submittedReports(): MorphMany
    {
        /** @var class-string<Report> $reportModel */
        $reportModel = config('reportable.models.report', Report::class);

        return $this->morphMany($reportModel, 'reporter');
    }

    public function hasReported(Model $reportable): bool
    {
        return $this->submittedReports()->forReportable($reportable)->exists();
    }

    public function hasActiveReportFor(Model $reportable): bool
    {
        return $this->submittedReports()->forReportable($reportable)->active()->exists();
    }
}
