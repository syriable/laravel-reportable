<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Event;
use Syriable\Reportable\Enums\ReportActionType;
use Syriable\Reportable\Events\ReportActionCreated;
use Syriable\Reportable\Facades\Reportable;
use Syriable\Reportable\Models\ReportAction;

it('logs an action for every status transition', function (): void {
    $moderator = createUser('Moderator');
    $report = createUser()->report(createPost(), 'spam');

    Reportable::review($report, $moderator, 'Taking a look.');
    Reportable::resolve($report, $moderator, 'Confirmed and handled.');

    expect($report->actions()->count())->toBe(2)
        ->and($report->actions()->pluck('action')->all())->toBe(['reviewed', 'resolved']);
});

it('records the performer and notes on transition actions', function (): void {
    $moderator = createUser('Moderator');
    $report = createUser()->report(createPost(), 'spam');

    Reportable::review($report, $moderator, 'Taking a look.');

    /** @var ReportAction $action */
    $action = $report->actions()->first();

    expect($action->performer->is($moderator))->toBeTrue()
        ->and($action->notes)->toBe('Taking a look.')
        ->and($action->created_at)->not->toBeNull();
});

it('records arbitrary custom actions with metadata', function (): void {
    $moderator = createUser('Moderator');
    $report = createUser()->report(createPost(), 'spam');

    $action = Reportable::recordAction(
        $report,
        ReportActionType::ContentHidden,
        $moderator,
        'Hidden pending review.',
        ['previous_visibility' => 'public'],
    );

    expect($action->action)->toBe('content_hidden')
        ->and($action->metadata)->toBe(['previous_visibility' => 'public'])
        ->and($action->report->is($report))->toBeTrue();
});

it('records free-form custom action names', function (): void {
    $report = createUser()->report(createPost(), 'spam');

    $action = Reportable::recordAction($report, 'escalated_to_legal');

    expect($action->action)->toBe('escalated_to_legal')
        ->and($action->performed_by_type)->toBeNull();
});

it('dispatches an event when an action is recorded', function (): void {
    Event::fake([ReportActionCreated::class]);

    $report = createUser()->report(createPost(), 'spam');

    Reportable::recordAction($report, ReportActionType::UserWarned);

    Event::assertDispatched(
        ReportActionCreated::class,
        fn (ReportActionCreated $event): bool => $event->action->action === 'user_warned',
    );
});

it('filters actions with the forAction scope', function (): void {
    $report = createUser()->report(createPost(), 'spam');

    Reportable::recordAction($report, ReportActionType::UserWarned);
    Reportable::recordAction($report, 'escalated_to_legal');

    expect(ReportAction::query()->forAction(ReportActionType::UserWarned)->count())->toBe(1)
        ->and(ReportAction::query()->forAction('escalated_to_legal')->count())->toBe(1);
});
