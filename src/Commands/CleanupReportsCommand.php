<?php

declare(strict_types=1);

namespace Syriable\Reportable\Commands;

use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Builder;
use Syriable\Reportable\Enums\ReportStatus;
use Syriable\Reportable\Models\Report;
use Syriable\Reportable\Models\ReportAction;

class CleanupReportsCommand extends Command
{
    protected $signature = 'reports:cleanup
        {--days= : Retention period in days (defaults to reportable.retention_days)}
        {--dry-run : Show how many reports would be deleted without deleting them}';

    protected $description = 'Permanently delete terminal and soft-deleted reports older than the retention period, including their action log.';

    public function handle(): int
    {
        $days = $this->option('days') !== null
            ? (int) $this->option('days')
            : (int) config('reportable.retention_days', 180);

        if ($days < 0) {
            $this->error('The retention period must be zero or a positive number of days.');

            return self::FAILURE;
        }

        $cutoff = now()->subDays($days);

        $query = $this->expiredReportsQuery($cutoff);

        $total = (clone $query)->count();

        if ($this->option('dry-run')) {
            $this->info("[dry-run] {$total} report(s) older than {$days} day(s) would be deleted.");

            return self::SUCCESS;
        }

        $deleted = 0;

        /** @var class-string<Report> $reportModel */
        $reportModel = config('reportable.models.report', Report::class);

        /** @var class-string<ReportAction> $actionModel */
        $actionModel = config('reportable.models.report_action', ReportAction::class);

        $keyName = (new $reportModel)->getKeyName();

        while (true) {
            $ids = (clone $query)->limit(500)->pluck($keyName);

            if ($ids->isEmpty()) {
                break;
            }

            $actionModel::query()->whereIn('report_id', $ids)->delete();

            $deleted += $reportModel::withTrashed()
                ->whereIn($keyName, $ids)
                ->forceDelete();
        }

        $this->info("Deleted {$deleted} report(s) older than {$days} day(s).");

        return self::SUCCESS;
    }

    protected function expiredReportsQuery(\DateTimeInterface $cutoff): Builder
    {
        /** @var class-string<Report> $reportModel */
        $reportModel = config('reportable.models.report', Report::class);

        return $reportModel::withTrashed()
            ->where(function (Builder $query) use ($cutoff): void {
                $query
                    ->where(function (Builder $query) use ($cutoff): void {
                        $query
                            ->whereIn('status', ReportStatus::terminalValues())
                            ->where('updated_at', '<', $cutoff);
                    })
                    ->orWhere(function (Builder $query) use ($cutoff): void {
                        $query
                            ->whereNotNull('deleted_at')
                            ->where('deleted_at', '<', $cutoff);
                    });
            });
    }
}
