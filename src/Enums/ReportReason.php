<?php

declare(strict_types=1);

namespace Syriable\Reportable\Enums;

/**
 * Default report reasons.
 *
 * The list of allowed reasons can be fully replaced through the
 * "reportable.reasons" config key, which is why reports store the reason
 * as a plain string instead of casting to this enum.
 */
enum ReportReason: string
{
    case Spam = 'spam';
    case Harassment = 'harassment';
    case Abuse = 'abuse';
    case Fake = 'fake';
    case Copyright = 'copyright';
    case Misinformation = 'misinformation';
    case Inappropriate = 'inappropriate';
    case Other = 'other';

    /**
     * @return list<string>
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
