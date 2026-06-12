<?php

declare(strict_types=1);

namespace Syriable\Reportable\Exceptions;

use Illuminate\Database\Eloquent\Model;

class DuplicateReportException extends ReportableException
{
    public static function for(Model $reporter, Model $reportable): self
    {
        return new self(sprintf(
            'Reporter [%s:%s] already has an active report on [%s:%s].',
            $reporter->getMorphClass(),
            $reporter->getKey(),
            $reportable->getMorphClass(),
            $reportable->getKey(),
        ));
    }
}
