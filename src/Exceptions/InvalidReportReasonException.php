<?php

declare(strict_types=1);

namespace Syriable\Reportable\Exceptions;

class InvalidReportReasonException extends ReportableException
{
    /**
     * @param  list<string>  $allowed
     */
    public static function make(string $reason, array $allowed): self
    {
        return new self(sprintf(
            'The report reason [%s] is not allowed. Allowed reasons: %s.',
            $reason,
            implode(', ', $allowed),
        ));
    }
}
