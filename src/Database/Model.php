<?php

namespace Core\Database;

use Core\Database\Concerns\{
    DisableDates,
    DisableMutators
};

abstract class Model extends DynamoModel
{
    // use DisableDates, DisableMutators;

    /** @var bool */
    // public static $snakeAttributes = false;
    /** @var array<string>|bool */
    // protected $guarded = [];
}
