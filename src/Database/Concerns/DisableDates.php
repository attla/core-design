<?php

namespace Core\Database\Concerns;

trait DisableDates
{
    /** @inheritdoc */
    public function getDates(): array
    {
        return [];
    }

    /** @inheritdoc */
    public function isDateCastable($key)
    {
        return false;
    }
}
