<?php

declare(strict_types=1);

use Illuminate\Support\Str;
use Syriable\Reportable\Facades\Reportable;

it('uses auto-incrementing ids by default', function (): void {
    $report = createUser()->report(createPost(), 'spam');

    expect($report->getKey())->toBeInt()
        ->and($report->getIncrementing())->toBeTrue();
});

it('supports uuid primary keys', function (): void {
    config()->set('reportable.id_type', 'uuid');
    $this->freshReportableTables();

    $report = createUser()->report(createPost(), 'spam');
    $action = Reportable::recordAction($report, 'custom_action');

    expect(Str::isUuid($report->getKey()))->toBeTrue()
        ->and(Str::isUuid($action->getKey()))->toBeTrue()
        ->and($report->getIncrementing())->toBeFalse()
        ->and($report->fresh()->reason)->toBe('spam');
});

it('supports ulid primary keys', function (): void {
    config()->set('reportable.id_type', 'ulid');
    $this->freshReportableTables();

    $report = createUser()->report(createPost(), 'spam');

    expect(Str::isUlid($report->getKey()))->toBeTrue()
        ->and($report->fresh()->getKey())->toBe($report->getKey());
});

it('soft deletes reports', function (): void {
    $report = createUser()->report(createPost(), 'spam');

    $report->delete();

    expect(\Syriable\Reportable\Models\Report::query()->count())->toBe(0)
        ->and(\Syriable\Reportable\Models\Report::withTrashed()->count())->toBe(1)
        ->and(\Syriable\Reportable\Models\Report::query()->find($report->getKey()))->toBeNull()
        ->and($report->fresh()->trashed())->toBeTrue();
});
