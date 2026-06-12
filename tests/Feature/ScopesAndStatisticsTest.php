<?php

declare(strict_types=1);

use Syriable\Reportable\Enums\ReportReason;
use Syriable\Reportable\Facades\Reportable;
use Syriable\Reportable\Models\Report;

beforeEach(function (): void {
    $moderator = createUser('Moderator');

    // pending (spam)
    createUser('A')->report(createPost('P1'), 'spam');

    // under review (abuse)
    Reportable::review(createUser('B')->report(createPost('P2'), 'abuse'), $moderator);

    // resolved (spam)
    Reportable::resolve(createUser('C')->report(createPost('P3'), 'spam'), $moderator);

    // rejected (fake)
    Reportable::reject(createUser('D')->report(createPost('P4'), 'fake'), $moderator);

    // closed (spam)
    Reportable::close(createUser('E')->report(createPost('P5'), 'spam'), $moderator);
});

it('filters reports through the status scopes', function (): void {
    expect(Report::query()->pending()->count())->toBe(1)
        ->and(Report::query()->underReview()->count())->toBe(1)
        ->and(Report::query()->resolved()->count())->toBe(1)
        ->and(Report::query()->rejected()->count())->toBe(1)
        ->and(Report::query()->closed()->count())->toBe(1)
        ->and(Report::query()->active()->count())->toBe(2)
        ->and(Report::query()->terminal()->count())->toBe(3);
});

it('filters reports by reason', function (): void {
    expect(Report::query()->forReason('spam')->count())->toBe(3)
        ->and(Report::query()->forReason(ReportReason::Abuse)->count())->toBe(1)
        ->and(Report::query()->forReason('fake')->count())->toBe(1);
});

it('provides statistics counts', function (): void {
    $stats = Reportable::statistics();

    expect($stats->pendingCount())->toBe(1)
        ->and($stats->underReviewCount())->toBe(1)
        ->and($stats->resolvedCount())->toBe(1)
        ->and($stats->rejectedCount())->toBe(1)
        ->and($stats->closedCount())->toBe(1)
        ->and($stats->activeCount())->toBe(2)
        ->and($stats->totalCount())->toBe(5);
});

it('provides per model statistics', function (): void {
    $post = createPost('Hot post');

    createUser('X')->report($post, 'spam');
    createUser('Y')->report($post, 'abuse');
    Reportable::resolve(createUser('Z')->report($post, 'fake'));

    $stats = Reportable::statistics();

    expect($stats->activeCountFor($post))->toBe(2)
        ->and($stats->countFor($post))->toBe(3);
});

it('groups counts by status and reason', function (): void {
    $stats = Reportable::statistics();

    expect($stats->countsByStatus())->toBe([
        'closed' => 1,
        'pending' => 1,
        'rejected' => 1,
        'resolved' => 1,
        'under_review' => 1,
    ])->and($stats->countsByReason())->toBe([
        'abuse' => 1,
        'fake' => 1,
        'spam' => 3,
    ]);
});
