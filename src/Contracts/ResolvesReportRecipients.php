<?php

declare(strict_types=1);

namespace Syriable\Reportable\Contracts;

use Syriable\Reportable\Models\Report;

interface ResolvesReportRecipients
{
    /**
     * Return the notifiables that should be notified about the report.
     *
     * @return iterable<mixed>
     */
    public function resolve(Report $report): iterable;
}
