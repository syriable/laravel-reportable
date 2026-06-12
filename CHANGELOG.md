# Changelog

All notable changes to `laravel-reportable` will be documented in this file.

## v1.0.0 - Unreleased

Initial release.

- Polymorphic reports (reporter, reportable, reviewer)
- Immutable report action log
- Report lifecycle: pending → under review → resolved / rejected / closed
- Anti-spam duplicate prevention + configurable cooldown
- Hybrid enum + config report reasons
- Query scopes and statistics helpers
- Report-created notifications (Laravel Notifications, mail channel)
- Full lifecycle events
- `reports:cleanup` artisan command with configurable retention
- UUID / ULID primary key support
- Soft deletes
- Optional spatie/laravel-activitylog integration
