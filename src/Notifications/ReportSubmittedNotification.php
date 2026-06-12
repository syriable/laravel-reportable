<?php

declare(strict_types=1);

namespace Syriable\Reportable\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Syriable\Reportable\Models\Report;

class ReportSubmittedNotification extends Notification
{
    use Queueable;

    public function __construct(
        public Report $report,
    ) {}

    /**
     * @return list<string>
     */
    public function via(object $notifiable): array
    {
        return config('reportable.notifications.channels', ['mail']);
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject(__('New report submitted'))
            ->line(__('A new report has been submitted.'))
            ->line(__('Reason: :reason', ['reason' => $this->report->reason]))
            ->line(__('Reported: :type #:id', [
                'type' => class_basename($this->report->reportable_type),
                'id' => $this->report->reportable_id,
            ]))
            ->line(__('Reported by: :type #:id', [
                'type' => class_basename($this->report->reporter_type),
                'id' => $this->report->reporter_id,
            ]));
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'report_id' => $this->report->getKey(),
            'reason' => $this->report->reason,
            'reportable_type' => $this->report->reportable_type,
            'reportable_id' => $this->report->reportable_id,
            'reporter_type' => $this->report->reporter_type,
            'reporter_id' => $this->report->reporter_id,
        ];
    }
}
