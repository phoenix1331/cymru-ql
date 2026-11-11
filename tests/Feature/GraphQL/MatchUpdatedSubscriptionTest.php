<?php

declare(strict_types=1);

use App\GraphQL\Subscriptions\MatchUpdated;
use App\Live\MatchFeed;
use App\Models\Fixture;
use Illuminate\Http\Request;
use Nuwave\Lighthouse\Subscriptions\BroadcastDriverManager;
use Nuwave\Lighthouse\Subscriptions\Subscriber;
use Nuwave\Lighthouse\Testing\TestsSubscriptions;

uses(TestsSubscriptions::class);

const MATCH_SUBSCRIPTION = 'subscription ($id: ID!) { matchUpdated(fixtureId: $id) { id status minute homeScore awayScore } }';

it('subscribes to a fixture and returns the channel to listen on', function () {
    $fixture = Fixture::factory()->live(12)->create();

    $response = $this->graphQL(MATCH_SUBSCRIPTION, ['id' => $fixture->id])->assertOk();

    expect($response->json('data.matchUpdated'))->toBeNull()
        ->and($response->json('extensions.lighthouse_subscriptions.channel'))->toStartWith('private-lighthouse-');
});

it('broadcasts updates only to subscribers of that fixture', function () {
    [$watched, $other] = Fixture::factory()->live(30)->count(2)->create();

    $this->graphQL(MATCH_SUBSCRIPTION, ['id' => $watched->id]);

    $watched->update(['home_score' => 1, 'minute' => 31]);
    app(MatchFeed::class)->publish($watched);
    app(MatchFeed::class)->publish($other);

    $broadcaster = app(BroadcastDriverManager::class)->driver();

    $broadcaster->shouldHaveReceived('broadcast')->once()->withArgs(
        fn (Subscriber $subscriber, array $data): bool => $data['data']['matchUpdated'] === [
            'id' => (string) $watched->id,
            'status' => 'Live',
            'minute' => 31,
            'homeScore' => 1,
            'awayScore' => 0,
        ],
    );
});

it('lets anyone watch and filters by fixture id', function () {
    $fixture = Fixture::factory()->create();
    $subscription = new MatchUpdated;
    $subscriber = Mockery::mock(Subscriber::class);
    $subscriber->args = ['fixtureId' => (string) $fixture->id];

    expect($subscription->authorize($subscriber, new Request))->toBeTrue()
        ->and($subscription->filter($subscriber, $fixture))->toBeTrue()
        ->and($subscription->filter($subscriber, Fixture::factory()->create()))->toBeFalse()
        ->and($subscription->filter($subscriber, 'not a fixture'))->toBeFalse();
});
