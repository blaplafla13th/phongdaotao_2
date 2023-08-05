<?php declare(strict_types=1);

namespace App\Enums;

use BenSampo\Enum\Enum;

final class ActionType extends Enum
{
    const CREATE = 0;
    const UPDATE = 1;
    const DELETE = 2;

}
