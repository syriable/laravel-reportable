<?php

declare(strict_types=1);

namespace Syriable\Reportable\Notifications\Recipients;

use Illuminate\Support\Facades\Notification;
use Syriable\Reportable\Contracts\ResolvesReportRecipients;
use Syriable\Reportable\Models\Report;

/**
 * Default resolver: notifies the email addresses configured under
 * "reportable.notifications.mail_to".
 */
class MailRecipientsResolver implements ResolvesReportRecipients
{
    public function resolve(Report $report): iterable
    {
        /** @var list<string> $addresses */
        $addresses = config('reportable.notifications.mail_to', []);

        foreach ($addresses as $address) {
            yield Notification::route('mail', $address);
        }
    }
}
