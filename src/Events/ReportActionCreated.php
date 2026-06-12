<?php

declare(strict_types=1);

namespace Syriable\Reportable\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Syriable\Reportable\Models\ReportAction;

class ReportActionCreated
{
    use Dispatchable;
    use SerializesModels;

    public function __construct(
        public ReportAction $action,
    ) {}
}
