<?php

declare(strict_types=1);

namespace App\Enum;

enum GuildRole: string
{
    case MEMBER = 'Member';
    case OFFICER = 'Officer';
    case GM = 'GM';
}
