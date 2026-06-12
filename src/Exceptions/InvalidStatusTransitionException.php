<?php

declare(strict_types=1);

namespace Syriable\Reportable\Exceptions;

use Syriable\Reportable\Enums\ReportStatus;

class InvalidStatusTransitionException extends ReportableException
{
    public static function between(ReportStatus $from, ReportStatus $to): self
    {
        return new self(sprintf(
            'A report cannot transition from [%s] to [%s].',
            $from->value,
            $to->value,
        ));
    }
}
