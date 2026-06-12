<?php

declare(strict_types=1);

namespace Syriable\Reportable;

use Illuminate\Support\Facades\Event;
use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;
use Syriable\Reportable\Commands\CleanupReportsCommand;
use Syriable\Reportable\Contracts\ResolvesReportRecipients;
use Syriable\Reportable\Events\ReportActionCreated;
use Syriable\Reportable\Events\ReportCreated;
use Syriable\Reportable\Listeners\RecordReportActivity;
use Syriable\Reportable\Listeners\SendReportCreatedNotification;
use Syriable\Reportable\Services\ReportService;

class ReportableServiceProvider extends PackageServiceProvider
{
    public function configurePackage(Package $package): void
    {
        /*
         * More info: https://github.com/spatie/laravel-package-tools
         */
        $package
            ->name('laravel-reportable')
            ->hasConfigFile()
            ->hasMigrations([
                'create_reports_table',
                'create_report_actions_table',
            ])
            ->hasCommand(CleanupReportsCommand::class);
    }

    public function packageRegistered(): void
    {
        $this->app->singleton(ReportService::class);

        $this->app->bind(ResolvesReportRecipients::class, function () {
            return $this->app->make(config('reportable.notifications.recipients_resolver'));
        });
    }

    public function packageBooted(): void
    {
        Event::listen(ReportCreated::class, SendReportCreatedNotification::class);

        if ($this->shouldRecordActivity()) {
            Event::listen(ReportCreated::class, RecordReportActivity::class);
            Event::listen(ReportActionCreated::class, RecordReportActivity::class);
        }
    }

    protected function shouldRecordActivity(): bool
    {
        return (bool) config('reportable.activity_log.enabled', false)
            && class_exists(\Spatie\Activitylog\ActivitylogServiceProvider::class);
    }
}
