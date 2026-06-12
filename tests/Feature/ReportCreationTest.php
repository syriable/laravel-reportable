<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Event;
use Syriable\Reportable\Enums\ReportReason;
use Syriable\Reportable\Enums\ReportStatus;
use Syriable\Reportable\Events\ReportCreated;
use Syriable\Reportable\Exceptions\InvalidReportReasonException;
use Syriable\Reportable\Facades\Reportable;
use Syriable\Reportable\Models\Report;

it('creates a report through the reporter trait', function (): void {
    $user = createUser();
    $post = createPost();

    $report = $user->report($post, 'spam');

    expect($report)->toBeInstanceOf(Report::class)
        ->and($report->status)->toBe(ReportStatus::Pending)
        ->and($report->reason)->toBe('spam')
        ->and($report->reporter->is($user))->toBeTrue()
        ->and($report->reportable->is($post))->toBeTrue();
});

it('creates a report through the facade', function (): void {
    $user = createUser();
    $post = createPost();

    $report = Reportable::report($user, $post, ReportReason::Harassment);

    expect($report->reason)->toBe('harassment')
        ->and($report->reasonEnum())->toBe(ReportReason::Harassment);
});

it('stores metadata on the report', function (): void {
    $report = createUser()->report(createPost(), 'spam', ['url' => 'https://example.com', 'severity' => 3]);

    expect($report->metadata)->toBe(['url' => 'https://example.com', 'severity' => 3]);
});

it('dispatches the report created event', function (): void {
    Event::fake([ReportCreated::class]);

    $report = createUser()->report(createPost(), 'spam');

    Event::assertDispatched(
        ReportCreated::class,
        fn (ReportCreated $event): bool => $event->report->is($report),
    );
});

it('rejects reasons that are not allowed', function (): void {
    createUser()->report(createPost(), 'not_a_reason');
})->throws(InvalidReportReasonException::class);

it('allows custom reasons configured via config', function (): void {
    config()->set('reportable.reasons', ['nsfw', 'off_topic']);

    $report = createUser()->report(createPost(), 'nsfw');

    expect($report->reason)->toBe('nsfw');
});

it('rejects default enum reasons when the config overrides the list', function (): void {
    config()->set('reportable.reasons', ['nsfw', 'off_topic']);

    createUser()->report(createPost(), ReportReason::Spam);
})->throws(InvalidReportReasonException::class);

it('exposes reportable side helpers', function (): void {
    $post = createPost();

    createUser('Alice')->report($post, 'spam');
    createUser('Bob')->report($post, 'abuse');

    expect($post->reports()->count())->toBe(2)
        ->and($post->reportCount())->toBe(2)
        ->and($post->hasActiveReports())->toBeTrue()
        ->and($post->activeReportCount())->toBe(2)
        ->and($post->reportsForReason('spam')->count())->toBe(1);
});

it('exposes reporter side helpers', function (): void {
    $user = createUser();
    $post = createPost();

    expect($user->hasReported($post))->toBeFalse()
        ->and($user->canReport($post))->toBeTrue();

    $user->report($post, 'spam');

    expect($user->hasReported($post))->toBeTrue()
        ->and($user->hasActiveReportFor($post))->toBeTrue()
        ->and($user->canReport($post))->toBeFalse()
        ->and($user->submittedReports()->count())->toBe(1);
});
