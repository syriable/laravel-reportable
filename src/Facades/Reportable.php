<?php

declare(strict_types=1);

namespace Syriable\Reportable\Facades;

use Illuminate\Support\Facades\Facade;
use Syriable\Reportable\Services\ReportService;

/**
 * @method static \Syriable\Reportable\Models\Report report(\Illuminate\Database\Eloquent\Model $reporter, \Illuminate\Database\Eloquent\Model $reportable, \Syriable\Reportable\Enums\ReportReason|string $reason, array $metadata = [])
 * @method static \Syriable\Reportable\Models\Report submit(\Syriable\Reportable\Data\ReportData $data)
 * @method static bool canReport(\Illuminate\Database\Eloquent\Model $reporter, \Illuminate\Database\Eloquent\Model $reportable)
 * @method static \Syriable\Reportable\Models\Report review(\Syriable\Reportable\Models\Report $report, \Illuminate\Database\Eloquent\Model $reviewer, ?string $notes = null)
 * @method static \Syriable\Reportable\Models\Report resolve(\Syriable\Reportable\Models\Report $report, ?\Illuminate\Database\Eloquent\Model $performer = null, ?string $notes = null)
 * @method static \Syriable\Reportable\Models\Report reject(\Syriable\Reportable\Models\Report $report, ?\Illuminate\Database\Eloquent\Model $performer = null, ?string $notes = null)
 * @method static \Syriable\Reportable\Models\Report close(\Syriable\Reportable\Models\Report $report, ?\Illuminate\Database\Eloquent\Model $performer = null, ?string $notes = null)
 * @method static \Syriable\Reportable\Models\ReportAction recordAction(\Syriable\Reportable\Models\Report $report, \Syriable\Reportable\Enums\ReportActionType|string $action, ?\Illuminate\Database\Eloquent\Model $performer = null, ?string $notes = null, array $metadata = [])
 * @method static \Syriable\Reportable\Models\Report addInternalNote(\Syriable\Reportable\Models\Report $report, string $note)
 * @method static \Syriable\Reportable\Support\ReportStatistics statistics()
 *
 * @see \Syriable\Reportable\Services\ReportService
 */
class Reportable extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return ReportService::class;
    }
}
