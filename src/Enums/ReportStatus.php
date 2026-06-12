<?php

declare(strict_types=1);

namespace Syriable\Reportable\Enums;

enum ReportStatus: string
{
    case Pending = 'pending';
    case UnderReview = 'under_review';
    case Resolved = 'resolved';
    case Rejected = 'rejected';
    case Closed = 'closed';

    /**
     * @return list<self>
     */
    public static function activeStatuses(): array
    {
        return [self::Pending, self::UnderReview];
    }

    /**
     * @return list<self>
     */
    public static function terminalStatuses(): array
    {
        return [self::Resolved, self::Rejected, self::Closed];
    }

    /**
     * @return list<string>
     */
    public static function activeValues(): array
    {
        return array_column(self::activeStatuses(), 'value');
    }

    /**
     * @return list<string>
     */
    public static function terminalValues(): array
    {
        return array_column(self::terminalStatuses(), 'value');
    }

    public function isActive(): bool
    {
        return in_array($this, self::activeStatuses(), true);
    }

    public function isTerminal(): bool
    {
        return ! $this->isActive();
    }

    /**
     * @return list<self>
     */
    public function allowedTransitions(): array
    {
        return match ($this) {
            self::Pending => [self::UnderReview, self::Resolved, self::Rejected, self::Closed],
            self::UnderReview => [self::Resolved, self::Rejected, self::Closed],
            self::Resolved, self::Rejected, self::Closed => [],
        };
    }

    public function canTransitionTo(self $status): bool
    {
        return in_array($status, $this->allowedTransitions(), true);
    }
}
