<?php

declare(strict_types=1);

namespace Syriable\Reportable\Support;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Syriable\Reportable\Models\Report;

class ReportStatistics
{
    public function pendingCount(): int
    {
        return $this->query()->pending()->count();
    }

    public function underReviewCount(): int
    {
        return $this->query()->underReview()->count();
    }

    public function resolvedCount(): int
    {
        return $this->query()->resolved()->count();
    }

    public function rejectedCount(): int
    {
        return $this->query()->rejected()->count();
    }

    public function closedCount(): int
    {
        return $this->query()->closed()->count();
    }

    public function activeCount(): int
    {
        return $this->query()->active()->count();
    }

    public function totalCount(): int
    {
        return $this->query()->count();
    }

    public function activeCountFor(Model $reportable): int
    {
        return $this->query()->forReportable($reportable)->active()->count();
    }

    public function countFor(Model $reportable): int
    {
        return $this->query()->forReportable($reportable)->count();
    }

    /**
     * @return array<string, int>
     */
    public function countsByStatus(): array
    {
        return $this->query()
            ->toBase()
            ->selectRaw('status, count(*) as aggregate')
            ->groupBy('status')
            ->pluck('aggregate', 'status')
            ->map(fn ($count): int => (int) $count)
            ->all();
    }

    /**
     * @return array<string, int>
     */
    public function countsByReason(): array
    {
        return $this->query()
            ->toBase()
            ->selectRaw('reason, count(*) as aggregate')
            ->groupBy('reason')
            ->pluck('aggregate', 'reason')
            ->map(fn ($count): int => (int) $count)
            ->all();
    }

    /**
     * @return Builder<Report>
     */
    protected function query(): Builder
    {
        /** @var class-string<Report> $reportModel */
        $reportModel = config('reportable.models.report', Report::class);

        return $reportModel::query();
    }
}
