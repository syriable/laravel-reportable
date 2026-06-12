<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Event;
use Syriable\Reportable\Enums\ReportStatus;
use Syriable\Reportable\Events\ReportClosed;
use Syriable\Reportable\Events\ReportRejected;
use Syriable\Reportable\Events\ReportResolved;
use Syriable\Reportable\Events\ReportReviewed;
use Syriable\Reportable\Exceptions\InvalidStatusTransitionException;
use Syriable\Reportable\Facades\Reportable;

it('moves a report into review and records the reviewer', function (): void {
    $moderator = createUser('Moderator');
    $report = createUser()->report(createPost(), 'spam');

    Reportable::review($report, $moderator);

    expect($report->status)->toBe(ReportStatus::UnderReview)
        ->and($report->reviewed_at)->not->toBeNull()
        ->and($report->reviewer->is($moderator))->toBeTrue();
});

it('resolves a report and sets resolved_at', function (): void {
    $moderator = createUser('Moderator');
    $report = createUser()->report(createPost(), 'spam');

    Reportable::review($report, $moderator);
    Reportable::resolve($report, $moderator);

    expect($report->status)->toBe(ReportStatus::Resolved)
        ->and($report->resolved_at)->not->toBeNull()
        ->and($report->isTerminal())->toBeTrue();
});

it('rejects a report', function (): void {
    $report = createUser()->report(createPost(), 'spam');

    Reportable::reject($report, createUser('Moderator'));

    expect($report->status)->toBe(ReportStatus::Rejected)
        ->and($report->resolved_at)->not->toBeNull();
});

it('closes a report and sets closed_at', function (): void {
    $report = createUser()->report(createPost(), 'spam');

    Reportable::close($report);

    expect($report->status)->toBe(ReportStatus::Closed)
        ->and($report->closed_at)->not->toBeNull();
});

it('does not allow leaving a terminal status', function (): void {
    $report = createUser()->report(createPost(), 'spam');

    Reportable::resolve($report);
    Reportable::review($report, createUser('Moderator'));
})->throws(InvalidStatusTransitionException::class);

it('does not allow resolving an already closed report', function (): void {
    $report = createUser()->report(createPost(), 'spam');

    Reportable::close($report);
    Reportable::resolve($report);
})->throws(InvalidStatusTransitionException::class);

it('dispatches lifecycle events on transitions', function (): void {
    Event::fake([
        ReportReviewed::class,
        ReportResolved::class,
    ]);

    $moderator = createUser('Moderator');
    $report = createUser()->report(createPost(), 'spam');

    Reportable::review($report, $moderator);
    Reportable::resolve($report, $moderator);

    Event::assertDispatched(ReportReviewed::class);
    Event::assertDispatched(ReportResolved::class);
});

it('dispatches rejected and closed events', function (): void {
    Event::fake([ReportRejected::class, ReportClosed::class]);

    Reportable::reject(createUser()->report(createPost(), 'spam'));
    Reportable::close(createUser('Other')->report(createPost('Other post'), 'spam'));

    Event::assertDispatched(ReportRejected::class);
    Event::assertDispatched(ReportClosed::class);
});

it('exposes allowed transitions on the status enum', function (): void {
    expect(ReportStatus::Pending->canTransitionTo(ReportStatus::UnderReview))->toBeTrue()
        ->and(ReportStatus::Pending->canTransitionTo(ReportStatus::Resolved))->toBeTrue()
        ->and(ReportStatus::UnderReview->canTransitionTo(ReportStatus::Pending))->toBeFalse()
        ->and(ReportStatus::Resolved->allowedTransitions())->toBe([])
        ->and(ReportStatus::Pending->isActive())->toBeTrue()
        ->and(ReportStatus::Closed->isTerminal())->toBeTrue();
});

it('stores internal notes for moderators', function (): void {
    $report = createUser()->report(createPost(), 'spam');

    Reportable::addInternalNote($report, 'Checked the content.');
    Reportable::addInternalNote($report, 'Confirmed spam.');

    expect($report->internal_notes)->toBe("Checked the content.\nConfirmed spam.");
});
