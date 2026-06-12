<?php

declare(strict_types=1);

use Syriable\Reportable\Models\Report;
use Syriable\Reportable\Models\ReportAction;
use Syriable\Reportable\Notifications\Recipients\MailRecipientsResolver;
use Syriable\Reportable\Notifications\ReportSubmittedNotification;

return [

    /*
    |--------------------------------------------------------------------------
    | Primary Key Type
    |--------------------------------------------------------------------------
    |
    | The primary key type used by the package tables. Supported values:
    | "id" (auto-incrementing big integer), "uuid", "ulid".
    |
    | Changing this after the migrations have run requires a fresh migration.
    |
    */

    'id_type' => 'id',

    /*
    |--------------------------------------------------------------------------
    | Morph Key Type
    |--------------------------------------------------------------------------
    |
    | The column type used for the polymorphic *_id columns (reporter,
    | reportable, reviewed_by, performed_by). Match this to the primary key
    | type of your application models. Supported values:
    | "numeric", "uuid", "ulid", "string".
    |
    */

    'morph_id_type' => 'numeric',

    /*
    |--------------------------------------------------------------------------
    | Models
    |--------------------------------------------------------------------------
    |
    | You may extend the package models and point the package at your own
    | implementations here. Custom models must extend the package models.
    |
    */

    'models' => [
        'report' => Report::class,
        'report_action' => ReportAction::class,
    ],

    /*
    |--------------------------------------------------------------------------
    | Table Names
    |--------------------------------------------------------------------------
    */

    'table_names' => [
        'reports' => 'reports',
        'report_actions' => 'report_actions',
    ],

    /*
    |--------------------------------------------------------------------------
    | Report Reasons
    |--------------------------------------------------------------------------
    |
    | When set to null the default reasons from the
    | Syriable\Reportable\Enums\ReportReason enum are used. Provide an array
    | of strings to fully replace the allowed reasons, e.g.:
    |
    | 'reasons' => ['spam', 'nsfw', 'off_topic'],
    |
    */

    'reasons' => null,

    /*
    |--------------------------------------------------------------------------
    | Anti-Spam: Cooldown
    |--------------------------------------------------------------------------
    |
    | A reporter can never open a second report on a reportable while they
    | already have an active (pending / under review) report on it. After
    | their report reaches a terminal state (resolved / rejected / closed)
    | they must additionally wait this many days before reporting the same
    | reportable again. Set to 0 to disable the cooldown.
    |
    */

    'report_cooldown_days' => 7,

    /*
    |--------------------------------------------------------------------------
    | Cleanup: Retention
    |--------------------------------------------------------------------------
    |
    | The `reports:cleanup` command permanently deletes terminal
    | (resolved / rejected / closed) and soft-deleted reports - including
    | their action log - once they are older than this many days.
    |
    */

    'retention_days' => 180,

    /*
    |--------------------------------------------------------------------------
    | Notifications
    |--------------------------------------------------------------------------
    |
    | A notification is sent ONLY when a report is created. Recipients are
    | resolved through the configured resolver, which must implement
    | Syriable\Reportable\Contracts\ResolvesReportRecipients. The default
    | resolver notifies the email addresses listed under "mail_to".
    |
    */

    'notifications' => [
        'enabled' => true,

        'notification' => ReportSubmittedNotification::class,

        'channels' => ['mail'],

        'recipients_resolver' => MailRecipientsResolver::class,

        // Email addresses used by the default MailRecipientsResolver.
        'mail_to' => [
            // 'moderators@example.com',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Activity Log Integration (optional)
    |--------------------------------------------------------------------------
    |
    | When enabled and spatie/laravel-activitylog is installed, report
    | creation and every recorded report action are also written to the
    | activity log.
    |
    */

    'activity_log' => [
        'enabled' => false,
        'log_name' => 'reportable',
    ],
];
