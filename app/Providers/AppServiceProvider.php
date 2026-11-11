<?php

declare(strict_types=1);

namespace App\Providers;

use App\Data\DataFiles;
use App\Import\Distances\OsrmClient;
use App\Import\Enrichment\NominatimClient;
use App\Import\Enrichment\WikidataClient;
use App\Simulation\ClubRatings;
use App\Simulation\MatchSimulator;
use App\Simulation\SeasonGenerator;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->bind(
            DataFiles::class,
            fn (): DataFiles => new DataFiles((string) config('cymru.data_path')),
        );

        $this->app->bind(
            WikidataClient::class,
            fn (): WikidataClient => new WikidataClient((string) config('cymru.http_user_agent')),
        );

        $this->app->bind(
            SeasonGenerator::class,
            fn (): SeasonGenerator => new SeasonGenerator(
                new MatchSimulator,
                new ClubRatings,
                (int) config('cymru.simulation.seed'),
                (string) config('cymru.simulation.kick_off_time'),
                (string) config('cymru.simulation.kick_off_timezone'),
            ),
        );

        $this->app->bind(
            OsrmClient::class,
            fn (): OsrmClient => new OsrmClient((string) config('cymru.http_user_agent')),
        );

        $this->app->bind(
            NominatimClient::class,
            fn (): NominatimClient => new NominatimClient((string) config('cymru.http_user_agent')),
        );
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}
