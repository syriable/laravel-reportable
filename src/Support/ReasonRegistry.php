<?php

declare(strict_types=1);

namespace Syriable\Reportable\Support;

use Syriable\Reportable\Enums\ReportReason;
use Syriable\Reportable\Exceptions\InvalidReportReasonException;

/**
 * Resolves the list of allowed report reasons from the hybrid
 * enum + config system.
 */
final class ReasonRegistry
{
    /**
     * @return list<string>
     */
    public static function all(): array
    {
        $configured = config('reportable.reasons');

        if (is_array($configured) && $configured !== []) {
            return array_values(array_map(
                fn (ReportReason|string $reason): string => self::normalize($reason),
                $configured,
            ));
        }

        return ReportReason::values();
    }

    public static function normalize(ReportReason|string $reason): string
    {
        return $reason instanceof ReportReason ? $reason->value : $reason;
    }

    public static function isValid(ReportReason|string $reason): bool
    {
        return in_array(self::normalize($reason), self::all(), true);
    }

    /**
     * @throws InvalidReportReasonException
     */
    public static function validate(ReportReason|string $reason): string
    {
        $normalized = self::normalize($reason);

        if (! self::isValid($normalized)) {
            throw InvalidReportReasonException::make($normalized, self::all());
        }

        return $normalized;
    }
}
