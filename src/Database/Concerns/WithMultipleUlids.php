<?php

namespace Core\Database\Concerns;

use Illuminate\Database\Eloquent\Concerns\HasUniqueStringIds;
use Illuminate\Support\Str;

trait WithMultipleUlids
{
    use HasUniqueStringIds {
        initializeHasUniqueStringIds as public initializeHasUniqueStringIds;
        resolveRouteBindingQuery as public resolveRouteBindingQuery;
        getKeyType as public getKeyType;
        getIncrementing as public getIncrementing;
    }

    /**
     * Get the columns that should receive a unique identifier.
     *
     * @var string[]
     */
    public array $uniqueIds = ['id', 'pk'];

    /**
     * Get the columns that should receive a unique identifier.
     *
     * @return string[]
     */
    public function uniqueIds()
    {
        return $this->uniqueIds;
    }

    /**
     * Generate a new ULID for the model.
     *
     * @return string
     */
    public function newUniqueId()
    {
        return Str::ulid()->toBase58();
    }

    /**
     * Determine if given key is valid.
     *
     * @param  mixed  $value
     * @return bool
     */
    protected function isValidUniqueId($value): bool
    {
        return Str::isUlid($value);
    }
}
