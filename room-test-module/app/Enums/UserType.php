<?php declare(strict_types=1);

namespace App\Enums;

use BenSampo\Enum\Enum;

final class UserType extends Enum
{
    const Banned = -1;
    const Administrator = 0;
    const Teacher = 1;
    const Employee = 2;

    public static function available(): array
    {
        return [
            UserType::Administrator,
            UserType::Teacher,
            UserType::Employee,
        ];
    }
}
