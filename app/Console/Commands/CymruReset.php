<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\Fixture;
use Carbon\CarbonImmutable;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Console\ConfirmableTrait;
use Throwable;

#[Signature('cymru:reset
    {--as-of= : Simulate results for fixtures before this date and time (default now)}
    {--force : Run without confirmation in production}')]
#[Description('Wipe the database, reseed the football data and resimulate the current season')]
final class CymruReset extends Command
{
    use ConfirmableTrait;

    public function handle(): int
    {
        if (! $this->confirmToProceed()) {
            return self::FAILURE;
        }

        $asOf = $this->option('as-of');

        if (is_string($asOf) && $asOf !== '') {
            try {
                config(['cymru.simulation.as_of' => CarbonImmutable::parse($asOf)->toIso8601String()]);
            } catch (Throwable) {
                $this->components->error("Could not understand --as-of \"{$asOf}\".");

                return self::FAILURE;
            }
        }

        $this->call('migrate:fresh', ['--seed' => true, '--force' => true]);

        $this->components->info(sprintf(
            'Reset complete: %d fixtures, %d played.',
            Fixture::query()->count(),
            Fixture::query()->finished()->count(),
        ));

        return self::SUCCESS;
    }
}
