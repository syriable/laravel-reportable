<?php

declare(strict_types=1);

namespace Syriable\Reportable\Listeners;

use Illuminate\Support\Facades\Notification;
use Syriable\Reportable\Contracts\ResolvesReportRecipients;
use Syriable\Reportable\Events\ReportCreated;
use Syriable\Reportable\Notifications\ReportSubmittedNotification;

class SendReportCreatedNotification
{
    public function handle(ReportCreated $event): void
    {
        if (! config('reportable.notifications.enabled', true)) {
            return;
        }

        /** @var ResolvesReportRecipients $resolver */
        $resolver = app(config('reportable.notifications.recipients_resolver'));

        $recipients = collect($resolver->resolve($event->report));

        if ($recipients->isEmpty()) {
            return;
        }

        /** @var class-string<\Illuminate\Notifications\Notification> $notificationClass */
        $notificationClass = config(
            'reportable.notifications.notification',
            ReportSubmittedNotification::class,
        );

        $notification = app()->makeWith($notificationClass, ['report' => $event->report]);

        Notification::send($recipients, $notification);
    }
}
