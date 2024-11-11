<?php

namespace Core\Database\Concerns;

trait DisableMutators
{
    /** @inheritdoc */
    public function hasGetMutator($key)
    {
        return false;
    }

    /** @inheritdoc */
    public function hasSetMutator($key)
    {
        return false;
    }
}
