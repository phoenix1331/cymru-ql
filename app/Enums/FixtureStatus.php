<?php

declare(strict_types=1);

namespace App\Enums;

enum FixtureStatus: string
{
    case Scheduled = 'scheduled';
    case Live = 'live';
    case Finished = 'finished';
}
