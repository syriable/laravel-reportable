<?php

declare(strict_types=1);

namespace Syriable\Reportable\Models\Concerns;

use Illuminate\Support\Str;

/**
 * Switches the model primary key between auto-increment, UUID and ULID
 * based on the "reportable.id_type" config value.
 */
trait HasConfigurableIds
{
    public function initializeHasConfigurableIds(): void
    {
        $this->usesUniqueIds = $this->reportableUsesUniqueIds();
    }

    public function getIncrementing(): bool
    {
        return ! $this->reportableUsesUniqueIds();
    }

    public function getKeyType(): string
    {
        return $this->reportableUsesUniqueIds() ? 'string' : 'int';
    }

    /**
     * @return list<string>
     */
    public function uniqueIds(): array
    {
        return $this->reportableUsesUniqueIds() ? [$this->getKeyName()] : [];
    }

    public function newUniqueId(): ?string
    {
        return match (config('reportable.id_type', 'id')) {
            'uuid' => (string) Str::orderedUuid(),
            'ulid' => strtolower((string) Str::ulid()),
            default => null,
        };
    }

    protected function reportableUsesUniqueIds(): bool
    {
        return in_array(config('reportable.id_type', 'id'), ['uuid', 'ulid'], true);
    }
}
