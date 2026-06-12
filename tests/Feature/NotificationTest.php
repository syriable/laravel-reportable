<?php

declare(strict_types=1);

use Illuminate\Notifications\AnonymousNotifiable;
use Illuminate\Support\Facades\Notification;
use Syriable\Reportable\Facades\Reportable;
use Syriable\Reportable\Notifications\ReportSubmittedNotification;

it('notifies configured recipients when a report is created', function (): void {
    Notification::fake();

    config()->set('reportable.notifications.mail_to', ['mods@example.com']);

    createUser()->report(createPost(), 'spam');

    Notification::assertSentOnDemand(
        ReportSubmittedNotification::class,
        fn (ReportSubmittedNotification $notification, array $channels, AnonymousNotifiable $notifiable): bool => $notifiable->routes['mail'] === 'mods@example.com',
    );
});

it('does not notify when notifications are disabled', function (): void {
    Notification::fake();

    config()->set('reportable.notifications.enabled', false);
    config()->set('reportable.notifications.mail_to', ['mods@example.com']);

    createUser()->report(createPost(), 'spam');

    Notification::assertNothingSent();
});

it('does not notify when there are no recipients', function (): void {
    Notification::fake();

    createUser()->report(createPost(), 'spam');

    Notification::assertNothingSent();
});

it('does not notify on report updates', function (): void {
    Notification::fake();

    config()->set('reportable.notifications.mail_to', ['mods@example.com']);

    $report = createUser()->report(createPost(), 'spam');

    Notification::assertCount(1);

    Reportable::review($report, createUser('Moderator'));
    Reportable::resolve($report);

    Notification::assertCount(1);
});

it('builds a mail message with the report details', function (): void {
    $report = createUser()->report(createPost(), 'spam');

    $mail = (new ReportSubmittedNotification($report))->toMail(new AnonymousNotifiable);

    expect($mail->subject)->toBe('New report submitted');
});
