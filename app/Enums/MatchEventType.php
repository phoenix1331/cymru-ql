<?php

declare(strict_types=1);

namespace App\Enums;

enum MatchEventType: string
{
    case KickOff = 'kick_off';
    case Goal = 'goal';
    case PenaltyGoal = 'penalty_goal';
    case OwnGoal = 'own_goal';
    case YellowCard = 'yellow_card';
    case RedCard = 'red_card';
    case HalfTime = 'half_time';
    case FullTime = 'full_time';

    public function isGoal(): bool
    {
        return in_array($this, [self::Goal, self::PenaltyGoal, self::OwnGoal], true);
    }
}
