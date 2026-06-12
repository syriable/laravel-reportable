<?php

declare(strict_types=1);

namespace Syriable\Reportable\Actions;

use Illuminate\Database\Eloquent\Model;
use Syriable\Reportable\Enums\ReportActionType;
use Syriable\Reportable\Enums\ReportStatus;
use Syriable\Reportable\Events\ReportClosed;
use Syriable\Reportable\Events\ReportRejected;
use Syriable\Reportable\Events\ReportResolved;
use Syriable\Reportable\Events\ReportReviewed;
use Syriable\Reportable\Exceptions\InvalidStatusTransitionException;
use Syriable\Reportable\Models\Report;

class TransitionReportStatus
{
    public function __construct(
        protected RecordReportAction $recordReportAction,
    ) {}

    /**
     * @throws InvalidStatusTransitionException
     */
    public function execute(
        Report $report,
        ReportStatus $to,
        ?Model $performer = null,
        ?string $notes = null,
    ): Report {
        if (! $report->status->canTransitionTo($to)) {
            throw InvalidStatusTransitionException::between($report->status, $to);
        }

        $now = $report->freshTimestamp();

        if ($performer !== null && $report->reviewed_by_id === null) {
            $report->reviewed_by_type = $performer->getMorphClass();
            $report->reviewed_by_id = $performer->getKey();
            $report->reviewed_at ??= $now;
        }

        match ($to) {
            ReportStatus::UnderReview => $report->reviewed_at ??= $now,
            ReportStatus::Resolved, ReportStatus::Rejected => $report->resolved_at = $now,
            ReportStatus::Closed => $report->closed_at = $now,
            ReportStatus::Pending => null,
        };

        $report->status = $to;
        $report->save();

        $this->recordReportAction->execute(
            $report,
            ReportActionType::forStatus($to),
            $performer,
            $notes,
        );

        event(match ($to) {
            ReportStatus::UnderReview => new ReportReviewed($report),
            ReportStatus::Resolved => new ReportResolved($report),
            ReportStatus::Rejected => new ReportRejected($report),
            ReportStatus::Closed => new ReportClosed($report),
            ReportStatus::Pending => new ReportReviewed($report),
        });

        return $report;
    }
}
