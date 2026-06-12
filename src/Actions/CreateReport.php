<?php

declare(strict_types=1);

namespace Syriable\Reportable\Actions;

use Illuminate\Database\Eloquent\Model;
use Syriable\Reportable\Data\ReportData;
use Syriable\Reportable\Enums\ReportStatus;
use Syriable\Reportable\Events\ReportCreated;
use Syriable\Reportable\Exceptions\DuplicateReportException;
use Syriable\Reportable\Exceptions\ReportCooldownException;
use Syriable\Reportable\Models\Report;
use Syriable\Reportable\Support\ReasonRegistry;

class CreateReport
{
    /**
     * @throws DuplicateReportException
     * @throws ReportCooldownException
     * @throws \Syriable\Reportable\Exceptions\InvalidReportReasonException
     */
    public function execute(ReportData $data): Report
    {
        $reason = ReasonRegistry::validate($data->reason);

        $this->ensureNotDuplicateOrCoolingDown($data->reporter, $data->reportable);

        /** @var class-string<Report> $reportModel */
        $reportModel = config('reportable.models.report', Report::class);

        $report = $reportModel::query()->create([
            'reporter_type' => $data->reporter->getMorphClass(),
            'reporter_id' => $data->reporter->getKey(),
            'reportable_type' => $data->reportable->getMorphClass(),
            'reportable_id' => $data->reportable->getKey(),
            'reason' => $reason,
            'status' => ReportStatus::Pending,
            'metadata' => $data->metadata !== [] ? $data->metadata : null,
        ]);

        event(new ReportCreated($report));

        return $report;
    }

    /**
     * @throws DuplicateReportException
     * @throws ReportCooldownException
     */
    public function ensureNotDuplicateOrCoolingDown(Model $reporter, Model $reportable): void
    {
        /** @var class-string<Report> $reportModel */
        $reportModel = config('reportable.models.report', Report::class);

        /** @var Report|null $latest */
        $latest = $reportModel::query()
            ->byReporter($reporter)
            ->forReportable($reportable)
            ->latest('created_at')
            ->first();

        if ($latest === null) {
            return;
        }

        if ($latest->isActive()) {
            throw DuplicateReportException::for($reporter, $reportable);
        }

        $cooldownDays = (int) config('reportable.report_cooldown_days', 7);

        if ($cooldownDays <= 0) {
            return;
        }

        $terminalAt = $latest->closed_at ?? $latest->resolved_at ?? $latest->updated_at;

        if ($terminalAt === null) {
            return;
        }

        $availableAt = $terminalAt->copy()->addDays($cooldownDays);

        if ($availableAt->isFuture()) {
            throw ReportCooldownException::until($reporter, $reportable, $availableAt);
        }
    }
}
