<?php

declare(strict_types=1);

use Syriable\Reportable\Facades\Reportable;
use Syriable\Reportable\Models\Report;
use Syriable\Reportable\Models\ReportAction;

function ageReport(Report $report, int $days): void
{
    $report->timestamps = false;
    $report->forceFill(['updated_at' => now()->subDays($days)])->save();
    $report->timestamps = true;
}

it('deletes terminal reports older than the retention period', function (): void {
    $old = createUser('A')->report(createPost('Old'), 'spam');
    Reportable::resolve($old);
    ageReport($old, 200);

    $recent = createUser('B')->report(createPost('Recent'), 'spam');
    Reportable::resolve($recent);

    $this->artisan('reports:cleanup')
        ->expectsOutputToContain('Deleted 1 report(s)')
        ->assertExitCode(0);

    expect(Report::withTrashed()->whereKey($old->getKey())->exists())->toBeFalse()
        ->and(Report::query()->whereKey($recent->getKey())->exists())->toBeTrue();
});

it('keeps active reports regardless of age', function (): void {
    $report = createUser()->report(createPost(), 'spam');
    ageReport($report, 400);

    $this->artisan('reports:cleanup')->assertExitCode(0);

    expect(Report::query()->whereKey($report->getKey())->exists())->toBeTrue();
});

it('deletes the action log together with the report', function (): void {
    $report = createUser()->report(createPost(), 'spam');
    Reportable::resolve($report, createUser('Moderator'));
    ageReport($report, 200);

    expect(ReportAction::query()->where('report_id', $report->getKey())->count())->toBe(1);

    $this->artisan('reports:cleanup')->assertExitCode(0);

    expect(ReportAction::query()->where('report_id', $report->getKey())->count())->toBe(0);
});

it('deletes old soft-deleted reports', function (): void {
    $report = createUser()->report(createPost(), 'spam');
    $report->delete();
    Report::withTrashed()->whereKey($report->getKey())->update(['deleted_at' => now()->subDays(200)]);

    $this->artisan('reports:cleanup')->assertExitCode(0);

    expect(Report::withTrashed()->whereKey($report->getKey())->exists())->toBeFalse();
});

it('respects the --days option', function (): void {
    $report = createUser()->report(createPost(), 'spam');
    Reportable::resolve($report);
    ageReport($report, 10);

    $this->artisan('reports:cleanup', ['--days' => 5])->assertExitCode(0);

    expect(Report::withTrashed()->whereKey($report->getKey())->exists())->toBeFalse();
});

it('does not delete anything on a dry run', function (): void {
    $report = createUser()->report(createPost(), 'spam');
    Reportable::resolve($report);
    ageReport($report, 200);

    $this->artisan('reports:cleanup', ['--dry-run' => true])
        ->expectsOutputToContain('[dry-run] 1 report(s)')
        ->assertExitCode(0);

    expect(Report::query()->whereKey($report->getKey())->exists())->toBeTrue();
});
