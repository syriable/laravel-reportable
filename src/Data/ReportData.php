<?php

declare(strict_types=1);

namespace Syriable\Reportable\Data;

use Illuminate\Database\Eloquent\Model;
use Syriable\Reportable\Enums\ReportReason;
use Syriable\Reportable\Support\ReasonRegistry;

final readonly class ReportData
{
    /**
     * @param  array<array-key, mixed>  $metadata
     */
    public function __construct(
        public Model $reporter,
        public Model $reportable,
        public ReportReason|string $reason,
        public array $metadata = [],
    ) {}

    public function reasonValue(): string
    {
        return ReasonRegistry::normalize($this->reason);
    }
}
