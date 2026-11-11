<?php

declare(strict_types=1);

namespace App\Live;

use App\Models\Fixture;
use Nuwave\Lighthouse\Execution\Utils\Subscription;

/**
 * Publishes live fixture changes to GraphQL subscribers.
 */
class MatchFeed
{
    public function publish(Fixture $fixture): void
    {
        Subscription::broadcast('matchUpdated', $fixture);
    }
}
