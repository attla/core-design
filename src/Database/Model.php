<?php

namespace Core\Database;

use Core\Database\Concerns\{
    DisableDates,
    DisableMutators
};
use Core\Traits\HasBuildEvents;

abstract class Model extends DynamoModel
{
    use HasBuildEvents;
    // use DisableDates, DisableMutators;

    /** @var bool */
    // public static $snakeAttributes = false;
    /** @var array<string>|bool */
    // protected $guarded = [];
}
