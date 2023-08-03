<?php declare(strict_types=1);

namespace App\Enums;

use BenSampo\Enum\Enum;

final class NotiType extends Enum
{
    const Email = 0;
    const SMS = 1;
}
