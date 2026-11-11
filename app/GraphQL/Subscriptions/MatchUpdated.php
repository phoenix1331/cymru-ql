<?php

declare(strict_types=1);

namespace App\GraphQL\Subscriptions;

use App\Models\Fixture;
use Illuminate\Http\Request;
use Nuwave\Lighthouse\Schema\Types\GraphQLSubscription;
use Nuwave\Lighthouse\Subscriptions\Subscriber;

/**
 * Pushes a fixture to everyone watching it whenever the live simulator
 * records a goal, a card, the half or full time whistle, or a new minute.
 */
final class MatchUpdated extends GraphQLSubscription
{
    /**
     * Anyone can watch a match; there are no user accounts.
     */
    public function authorize(Subscriber $subscriber, Request $request): bool
    {
        return true;
    }

    /**
     * Only send the update to subscribers of this fixture.
     */
    public function filter(Subscriber $subscriber, mixed $root): bool
    {
        return $root instanceof Fixture && (string) $root->id === (string) ($subscriber->args['fixtureId'] ?? '');
    }
}
