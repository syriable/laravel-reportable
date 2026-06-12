<?php

declare(strict_types=1);

namespace Syriable\Reportable\Actions;

use Illuminate\Database\Eloquent\Model;
use Syriable\Reportable\Enums\ReportActionType;
use Syriable\Reportable\Events\ReportActionCreated;
use Syriable\Reportable\Models\Report;
use Syriable\Reportable\Models\ReportAction;

class RecordReportAction
{
    /**
     * @param  array<array-key, mixed>  $metadata
     */
    public function execute(
        Report $report,
        ReportActionType|string $action,
        ?Model $performer = null,
        ?string $notes = null,
        array $metadata = [],
    ): ReportAction {
        /** @var class-string<ReportAction> $actionModel */
        $actionModel = config('reportable.models.report_action', ReportAction::class);

        $reportAction = $actionModel::query()->create([
            'report_id' => $report->getKey(),
            'action' => $action instanceof ReportActionType ? $action->value : $action,
            'performed_by_type' => $performer?->getMorphClass(),
            'performed_by_id' => $performer?->getKey(),
            'notes' => $notes,
            'metadata' => $metadata !== [] ? $metadata : null,
        ]);

        event(new ReportActionCreated($reportAction));

        return $reportAction;
    }
}
