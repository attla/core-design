<?php

namespace Core\Enums;

// font: https://github.com/archtechx/enums
use ArchTech\Enums\Comparable;

enum ValidateOn
{
    use Comparable;

    case ALL;
    case UPDATE;
    case CREATE;
    case DELETE;
}
