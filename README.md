# Laravel Reportable

[![Latest Version on Packagist](https://img.shields.io/packagist/v/syriable/laravel-reportable.svg?style=flat-square)](https://packagist.org/packages/syriable/laravel-reportable)
[![GitHub Tests Action Status](https://img.shields.io/github/actions/workflow/status/syriable/laravel-reportable/run-tests.yml?branch=main&label=tests&style=flat-square)](https://github.com/syriable/laravel-reportable/actions?query=workflow%3Arun-tests+branch%3Amain)
[![Total Downloads](https://img.shields.io/packagist/dt/syriable/laravel-reportable.svg?style=flat-square)](https://packagist.org/packages/syriable/laravel-reportable)

A generic **Reporting & Moderation Tracking System** for Laravel applications.

Make any Eloquent model reportable and let any authenticatable entity create reports. The package **does not perform moderation actions itself** — it tracks reports, moderation decisions, and moderation actions, providing a full, immutable audit history with an extensible workflow.

- ✅ Polymorphic reporters, reportables, and reviewers
- ✅ Immutable report action log (`report_actions`)
- ✅ Lifecycle workflow: `pending → under_review → resolved | rejected | closed`
- ✅ Anti-spam: duplicate prevention + configurable cooldown
- ✅ Hybrid enum + config report reasons
- ✅ Query scopes & statistics
- ✅ Notifications on report creation
- ✅ Events for the full lifecycle
- ✅ Metadata (JSON) & moderator-only internal notes
- ✅ Cleanup command with configurable retention
- ✅ UUID / ULID support, soft deletes, optional activity log integration

## Installation

Install the package via composer:

```bash
composer require syriable/laravel-reportable
```

Publish and run the migrations:

```bash
php artisan vendor:publish --tag="laravel-reportable-migrations"
php artisan migrate
```

Publish the config file:

```bash
php artisan vendor:publish --tag="laravel-reportable-config"
```

## Usage

### 1. Make models reportable

Add the `HasReports` trait to any model that can be reported (Post, Comment, User, Message, Media, ...):

```php
use Syriable\Reportable\Concerns\HasReports;

class Post extends Model
{
    use HasReports;
}
```

### 2. Allow entities to report

Add the `CanReport` trait to any model that can create reports (User, Admin, Moderator, ...):

```php
use Syriable\Reportable\Concerns\CanReport;

class User extends Authenticatable
{
    use CanReport;
}
```

### 3. Create reports

```php
$user->report($post, 'spam');

// with an enum reason and metadata
use Syriable\Reportable\Enums\ReportReason;

$user->report($post, ReportReason::Harassment, [
    'url' => 'https://example.com/posts/1',
    'context' => 'Found via search',
]);

// or via the facade / service
use Syriable\Reportable\Facades\Reportable;

Reportable::report($user, $post, 'spam');
```

### 4. Inspect reports

```php
// Reportable side
$post->reports();          // MorphMany
$post->activeReports();    // pending + under review
$post->hasActiveReports(); // bool
$post->reportCount();      // int
$post->activeReportCount();
$post->reportsForReason('spam');

// Reporter side
$user->submittedReports();
$user->hasReported($post);
$user->hasActiveReportFor($post);
$user->canReport($post);
```

## Workflow

A report moves through the following lifecycle:

```
Pending → UnderReview → Resolved | Rejected | Closed
```

Transitions are driven through the service (or the `Reportable` facade). Every transition automatically writes an immutable entry to the action log:

```php
use Syriable\Reportable\Facades\Reportable;

Reportable::review($report, $moderator, 'Taking a look.');    // → under_review
Reportable::resolve($report, $moderator, 'Content removed.'); // → resolved
Reportable::reject($report, $moderator, 'Not a violation.');  // → rejected
Reportable::close($report, $moderator);                       // → closed
```

Invalid transitions (e.g. resolving an already closed report) throw an
`InvalidStatusTransitionException`. Terminal statuses cannot be left.

> [!NOTE]
> A report may also be resolved / rejected / closed directly from `pending`,
> without passing through `under_review`.

## Report actions (audit log)

Every moderation decision is stored as an **immutable action log entry** in the
`report_actions` table. The package never executes the action itself — your
application hides content, bans users, etc. The package only records that it happened:

```php
use Syriable\Reportable\Enums\ReportActionType;

Reportable::recordAction($report, ReportActionType::ContentHidden, $moderator, 'Hidden pending review.');
Reportable::recordAction($report, ReportActionType::UserBanned, $admin, null, ['duration' => '30 days']);

// custom, free-form actions are fully supported
Reportable::recordAction($report, 'escalated_to_legal', $admin);

$report->actions; // chronological audit history
```

Built-in action types: `reviewed`, `resolved`, `rejected`, `closed`, `content_hidden`,
`content_deleted`, `user_warned`, `user_suspended`, `user_banned` — plus any custom string.

## Anti-spam rules

A reporter can never open a second report on the same reportable while they already
have an **active** report (pending / under review) on it — this throws a
`DuplicateReportException`.

After their report reaches a terminal state, a configurable cooldown applies before
they may report the same reportable again (`ReportCooldownException`):

```php
// config/reportable.php
'report_cooldown_days' => 7, // 0 disables the cooldown
```

Check ahead of time with `$user->canReport($post)` or `Reportable::canReport($user, $post)`.

## Reasons (enum + config hybrid)

Default reasons come from the `ReportReason` enum: `spam`, `harassment`, `abuse`,
`fake`, `copyright`, `misinformation`, `inappropriate`, `other`.

Override the allowed list entirely via config:

```php
'reasons' => ['nsfw', 'off_topic', 'spam'],
```

Submitting a reason that is not allowed throws an `InvalidReportReasonException`.

## Query scopes

```php
use Syriable\Reportable\Models\Report;

Report::pending()->get();
Report::underReview()->get();
Report::resolved()->get();
Report::rejected()->get();
Report::closed()->get();
Report::active()->get();      // pending + under review
Report::terminal()->get();    // resolved + rejected + closed
Report::forReason('spam')->get();
Report::forReportable($post)->get();
Report::byReporter($user)->get();
```

## Statistics

```php
$stats = Reportable::statistics();

$stats->pendingCount();
$stats->underReviewCount();
$stats->resolvedCount();
$stats->rejectedCount();
$stats->closedCount();
$stats->activeCount();
$stats->totalCount();

$stats->activeCountFor($post); // active reports for one model
$stats->countFor($post);

$stats->countsByStatus(); // ['pending' => 3, 'resolved' => 10, ...]
$stats->countsByReason(); // ['spam' => 8, 'abuse' => 2, ...]
```

## Notifications

A notification is sent **only when a report is created** — never on updates.
Recipients are resolved through a configurable resolver. The default resolver
emails the addresses configured under `notifications.mail_to`:

```php
'notifications' => [
    'enabled' => true,
    'notification' => ReportSubmittedNotification::class,
    'channels' => ['mail'],
    'recipients_resolver' => MailRecipientsResolver::class,
    'mail_to' => ['moderators@example.com'],
],
```

To notify your own admin/moderator models, implement
`Syriable\Reportable\Contracts\ResolvesReportRecipients`:

```php
class AdminRecipients implements ResolvesReportRecipients
{
    public function resolve(Report $report): iterable
    {
        return Admin::where('receives_reports', true)->get();
    }
}
```

## Events

| Event | Dispatched when |
| --- | --- |
| `ReportCreated` | a report is submitted |
| `ReportUpdated` | a report model is updated |
| `ReportReviewed` | a report moves to under review |
| `ReportResolved` | a report is resolved |
| `ReportRejected` | a report is rejected |
| `ReportClosed` | a report is closed |
| `ReportActionCreated` | an action log entry is recorded |

All events live in `Syriable\Reportable\Events` and expose a public `$report`
(or `$action` for `ReportActionCreated`).

## Metadata & internal notes

Both reports and report actions carry a free-form JSON `metadata` column for
future extensibility. Reports additionally have a moderator-only
`internal_notes` text column:

```php
Reportable::addInternalNote($report, 'Verified with the trust & safety team.');
```

## Cleanup

Permanently delete terminal (resolved / rejected / closed) and soft-deleted
reports — including their action log — once they are older than the retention period:

```bash
php artisan reports:cleanup
php artisan reports:cleanup --days=90
php artisan reports:cleanup --dry-run
```

```php
'retention_days' => 180,
```

Schedule it in your application:

```php
Schedule::command('reports:cleanup')->daily();
```

## UUID / ULID support

```php
'id_type' => 'id',            // 'id', 'uuid', or 'ulid' — primary keys of the package tables
'morph_id_type' => 'numeric', // 'numeric', 'uuid', 'ulid', or 'string' — match your app models
```

## Activity log integration (optional)

If [spatie/laravel-activitylog](https://github.com/spatie/laravel-activitylog) is
installed, enable mirror logging of report creation and recorded actions:

```php
'activity_log' => [
    'enabled' => true,
    'log_name' => 'reportable',
],
```

## Extending the models

Point the package at your own models (they must extend the package models):

```php
'models' => [
    'report' => App\Models\Report::class,
    'report_action' => App\Models\ReportAction::class,
],
```

## Design philosophy

- **Opinionated core** — immutable reports & action log, strict workflow.
- **Flexible configuration layer** — reasons, cooldown, retention, models, tables, keys.
- **Extensible via events** — hook into every lifecycle step.
- **No tight coupling** to your application domain.
- **No enforcement of punishments** — the package records moderation decisions, it never executes them.

## Testing

```bash
composer test
```

## Changelog

Please see [CHANGELOG](CHANGELOG.md) for more information on what has changed recently.

## Credits

- [Syriable](https://github.com/syriable)
- [All Contributors](../../contributors)

Based on [spatie/package-skeleton-laravel](https://github.com/spatie/package-skeleton-laravel).

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.
