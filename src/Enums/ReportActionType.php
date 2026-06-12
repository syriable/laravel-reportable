<?php

declare(strict_types=1);

namespace Syriable\Reportable\Enums;

/**
 * Well-known report action types.
 *
 * The report_actions.action column is a plain string, so applications are
 * free to record any custom action name alongside these defaults.
 */
enum ReportActionType: string
{
    case Reviewed = 'reviewed';
    case Resolved = 'resolved';
    case Rejected = 'rejected';
    case Closed = 'closed';
    case ContentHidden = 'content_hidden';
    case ContentDeleted = 'content_deleted';
    case UserWarned = 'user_warned';
    case UserSuspended = 'user_suspended';
    case UserBanned = 'user_banned';

    public static function forStatus(ReportStatus $status): self
    {
        return match ($status) {
            ReportStatus::UnderReview => self::Reviewed,
            ReportStatus::Resolved => self::Resolved,
            ReportStatus::Rejected => self::Rejected,
            ReportStatus::Closed => self::Closed,
            ReportStatus::Pending => self::Reviewed,
        };
    }
}
