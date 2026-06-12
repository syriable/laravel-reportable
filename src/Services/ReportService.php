<?php

declare(strict_types=1);

namespace Syriable\Reportable\Services;

use Illuminate\Database\Eloquent\Model;
use Syriable\Reportable\Actions\CreateReport;
use Syriable\Reportable\Actions\RecordReportAction;
use Syriable\Reportable\Actions\TransitionReportStatus;
use Syriable\Reportable\Data\ReportData;
use Syriable\Reportable\Enums\ReportActionType;
use Syriable\Reportable\Enums\ReportReason;
use Syriable\Reportable\Enums\ReportStatus;
use Syriable\Reportable\Exceptions\ReportableException;
use Syriable\Reportable\Models\Report;
use Syriable\Reportable\Models\ReportAction;
use Syriable\Reportable\Support\ReportStatistics;

class ReportService
{
    public function __construct(
        protected CreateReport $createReport,
        protected TransitionReportStatus $transitionReportStatus,
        protected RecordReportAction $recordReportAction,
    ) {}

    /**
     * Submit a new report.
     *
     * @param  array<array-key, mixed>  $metadata
     *
     * @throws \Syriable\Reportable\Exceptions\DuplicateReportException
     * @throws \Syriable\Reportable\Exceptions\ReportCooldownException
     * @throws \Syriable\Reportable\Exceptions\InvalidReportReasonException
     */
    public function report(
        Model $reporter,
        Model $reportable,
        ReportReason|string $reason,
        array $metadata = [],
    ): Report {
        return $this->submit(new ReportData($reporter, $reportable, $reason, $metadata));
    }

    public function submit(ReportData $data): Report
    {
        return $this->createReport->execute($data);
    }

    /**
     * Determine whether the reporter may currently report the reportable.
     */
    public function canReport(Model $reporter, Model $reportable): bool
    {
        try {
            $this->createReport->ensureNotDuplicateOrCoolingDown($reporter, $reportable);
        } catch (ReportableException) {
            return false;
        }

        return true;
    }

    /**
     * Move a report into review.
     */
    public function review(Report $report, Model $reviewer, ?string $notes = null): Report
    {
        return $this->transitionReportStatus->execute($report, ReportStatus::UnderReview, $reviewer, $notes);
    }

    public function resolve(Report $report, ?Model $performer = null, ?string $notes = null): Report
    {
        return $this->transitionReportStatus->execute($report, ReportStatus::Resolved, $performer, $notes);
    }

    public function reject(Report $report, ?Model $performer = null, ?string $notes = null): Report
    {
        return $this->transitionReportStatus->execute($report, ReportStatus::Rejected, $performer, $notes);
    }

    public function close(Report $report, ?Model $performer = null, ?string $notes = null): Report
    {
        return $this->transitionReportStatus->execute($report, ReportStatus::Closed, $performer, $notes);
    }

    /**
     * Record an arbitrary moderation action on a report.
     *
     * The package never performs the action itself - it only records that
     * the action happened.
     *
     * @param  array<array-key, mixed>  $metadata
     */
    public function recordAction(
        Report $report,
        ReportActionType|string $action,
        ?Model $performer = null,
        ?string $notes = null,
        array $metadata = [],
    ): ReportAction {
        return $this->recordReportAction->execute($report, $action, $performer, $notes, $metadata);
    }

    /**
     * Set or append moderator-only internal notes on a report.
     */
    public function addInternalNote(Report $report, string $note): Report
    {
        $report->internal_notes = $report->internal_notes === null
            ? $note
            : $report->internal_notes."\n".$note;

        $report->save();

        return $report;
    }

    public function statistics(): ReportStatistics
    {
        return new ReportStatistics;
    }
}
