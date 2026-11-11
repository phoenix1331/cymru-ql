<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Enums\MatchEventType;
use App\Live\LiveRound;
use App\Models\Fixture;
use App\Models\MatchEvent;
use App\Services\SeasonService;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Sleep;

#[Signature('cymru:simulate
    {--tick=1 : Real seconds per match minute}
    {--pause=30 : Seconds to wait between rounds}
    {--rounds=0 : Stop after this many rounds (0 keeps going until the season ends)}')]
#[Description('Play the next round of fixtures live, pushing goals and score changes to GraphQL subscribers')]
final class CymruSimulate extends Command
{
    private const array REPORTED = [MatchEventType::Goal, MatchEventType::PenaltyGoal, MatchEventType::OwnGoal, MatchEventType::RedCard, MatchEventType::FullTime];

    public function handle(LiveRound $live, SeasonService $seasons): int
    {
        $season = $seasons->current();
        $limit = max(0, (int) $this->option('rounds'));
        $played = 0;

        while ($limit === 0 || $played < $limit) {
            $fixtures = $live->next($season);

            if ($fixtures->isEmpty()) {
                $this->components->info("Every {$season->name} fixture has been played. Run php artisan cymru:reset to start again.");

                return self::SUCCESS;
            }

            $this->components->info(sprintf('Kick off: round %d, %d fixtures.', $fixtures->firstOrFail()->round, $fixtures->count()));

            $live->play($fixtures, (float) $this->option('tick'), function (Fixture $fixture, MatchEvent $event): void {
                if (in_array($event->type, self::REPORTED, true)) {
                    $this->line(sprintf(
                        "  %d' %s v %s: %s",
                        $event->minute,
                        $fixture->homeClub->name,
                        $fixture->awayClub->name,
                        $event->description,
                    ));
                }
            });

            $played++;

            if ($limit === 0 || $played < $limit) {
                Sleep::for(max(0, (int) $this->option('pause')))->seconds();
            }
        }

        return self::SUCCESS;
    }
}
