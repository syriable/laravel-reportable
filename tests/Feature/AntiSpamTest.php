<?php

declare(strict_types=1);

use Illuminate\Support\Carbon;
use Syriable\Reportable\Exceptions\DuplicateReportException;
use Syriable\Reportable\Exceptions\ReportCooldownException;
use Syriable\Reportable\Facades\Reportable;

it('prevents a duplicate report while one is still active', function (): void {
    $user = createUser();
    $post = createPost();

    $user->report($post, 'spam');

    expect(fn () => $user->report($post, 'abuse'))
        ->toThrow(DuplicateReportException::class);
});

it('prevents a duplicate report while one is under review', function (): void {
    $user = createUser();
    $moderator = createUser('Moderator');
    $post = createPost();

    $report = $user->report($post, 'spam');
    Reportable::review($report, $moderator);

    expect(fn () => $user->report($post, 'spam'))
        ->toThrow(DuplicateReportException::class);
});

it('allows different reporters to report the same reportable', function (): void {
    $post = createPost();

    createUser('Alice')->report($post, 'spam');
    createUser('Bob')->report($post, 'spam');

    expect($post->reportCount())->toBe(2);
});

it('allows the same reporter to report different reportables', function (): void {
    $user = createUser();

    $user->report(createPost('One'), 'spam');
    $user->report(createPost('Two'), 'spam');

    expect($user->submittedReports()->count())->toBe(2);
});

it('enforces the cooldown after a report is resolved', function (): void {
    $user = createUser();
    $post = createPost();

    $report = $user->report($post, 'spam');
    Reportable::resolve($report);

    expect(fn () => $user->report($post, 'spam'))
        ->toThrow(ReportCooldownException::class);
});

it('allows reporting again once the cooldown has passed', function (): void {
    $user = createUser();
    $post = createPost();

    $report = $user->report($post, 'spam');
    Reportable::resolve($report);

    Carbon::setTestNow(now()->addDays(8));

    $second = $user->report($post, 'spam');

    expect($second->exists)->toBeTrue()
        ->and($post->reportCount())->toBe(2);
});

it('still blocks within the configured cooldown window', function (): void {
    config()->set('reportable.report_cooldown_days', 30);

    $user = createUser();
    $post = createPost();

    Reportable::resolve($user->report($post, 'spam'));

    Carbon::setTestNow(now()->addDays(8));

    expect(fn () => $user->report($post, 'spam'))
        ->toThrow(ReportCooldownException::class);
});

it('disables the cooldown when set to zero', function (): void {
    config()->set('reportable.report_cooldown_days', 0);

    $user = createUser();
    $post = createPost();

    Reportable::resolve($user->report($post, 'spam'));

    $second = $user->report($post, 'spam');

    expect($second->exists)->toBeTrue();
});

it('reports canReport correctly during the lifecycle', function (): void {
    $user = createUser();
    $post = createPost();

    expect(Reportable::canReport($user, $post))->toBeTrue();

    $report = $user->report($post, 'spam');

    expect(Reportable::canReport($user, $post))->toBeFalse();

    Reportable::resolve($report);

    expect(Reportable::canReport($user, $post))->toBeFalse();

    Carbon::setTestNow(now()->addDays(8));

    expect(Reportable::canReport($user, $post))->toBeTrue();
});
