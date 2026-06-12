<?php

declare(strict_types=1);

namespace Syriable\Reportable\Exceptions;

use DateTimeInterface;
use Illuminate\Database\Eloquent\Model;

class ReportCooldownException extends ReportableException
{
    public static function until(Model $reporter, Model $reportable, DateTimeInterface $availableAt): self
    {
        return new self(sprintf(
            'Reporter [%s:%s] must wait until %s before reporting [%s:%s] again.',
            $reporter->getMorphClass(),
            $reporter->getKey(),
            $availableAt->format(DateTimeInterface::ATOM),
            $reportable->getMorphClass(),
            $reportable->getKey(),
        ));
    }
}
