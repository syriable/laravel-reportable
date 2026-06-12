<?php

declare(strict_types=1);

namespace Syriable\Reportable\Listeners;

use Syriable\Reportable\Events\ReportActionCreated;
use Syriable\Reportable\Events\ReportCreated;

/**
 * Optional spatie/laravel-activitylog integration. Only registered when the
 * package is installed and "reportable.activity_log.enabled" is true.
 */
class RecordReportActivity
{
    public function handle(ReportCreated|ReportActionCreated $event): void
    {
        if (! function_exists('activity')) {
            return;
        }

        $logName = config('reportable.activity_log.log_name', 'reportable');

        if ($event instanceof ReportCreated) {
            activity($logName)
                ->performedOn($event->report)
                ->withProperties([
                    'reason' => $event->report->reason,
                    'reporter_type' => $event->report->reporter_type,
                    'reporter_id' => $event->report->reporter_id,
                ])
                ->event('report.created')
                ->log('Report created');

            return;
        }

        activity($logName)
            ->performedOn($event->action)
            ->withProperties([
                'report_id' => $event->action->report_id,
                'action' => $event->action->action,
                'performed_by_type' => $event->action->performed_by_type,
                'performed_by_id' => $event->action->performed_by_id,
            ])
            ->event('report.action_created')
            ->log('Report action recorded');
    }
}
