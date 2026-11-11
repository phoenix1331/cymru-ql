<?php

declare(strict_types=1);

namespace App\Providers;

use App\Data\DataFiles;
use App\Enums\FixtureStatus;
use App\Enums\MatchEventType;
use App\GraphQL\ExampleQueries;
use App\GraphQL\Listeners\AddQueryMetricsExtension;
use App\GraphQL\Validation\ValidationRulesProvider;
use App\Import\Distances\OsrmClient;
use App\Import\Enrichment\NominatimClient;
use App\Import\Enrichment\WikidataClient;
use App\Simulation\ClubRatings;
use App\Simulation\MatchPlanner;
use App\Simulation\MatchSimulator;
use App\Simulation\SeasonGenerator;
use App\Support\QueryMetrics;
use GraphQL\Type\Definition\PhpEnumType;
use Illuminate\Database\Events\QueryExecuted;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;
use Nuwave\Lighthouse\Events\BuildExtensionsResponse;
use Nuwave\Lighthouse\Schema\TypeRegistry;
use Nuwave\Lighthouse\Support\Contracts\ProvidesValidationRules;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(QueryMetrics::class);
        $this->app->bind(ProvidesValidationRules::class, ValidationRulesProvider::class);

        $this->app->bind(
            DataFiles::class,
            fn (): DataFiles => new DataFiles((string) config('cymru.data_path')),
        );

        $this->app->bind(
            ExampleQueries::class,
            fn (): ExampleQueries => new ExampleQueries(base_path('graphql/examples')),
        );

        $this->app->bind(
            WikidataClient::class,
            fn (): WikidataClient => new WikidataClient((string) config('cymru.http_user_agent')),
        );

        $this->app->bind(
            MatchPlanner::class,
            fn (): MatchPlanner => new MatchPlanner(new MatchSimulator, (int) config('cymru.simulation.seed')),
        );

        $this->app->bind(
            SeasonGenerator::class,
            fn (): SeasonGenerator => new SeasonGenerator(
                $this->app->make(MatchPlanner::class),
                new ClubRatings,
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
    public function boot(TypeRegistry $types): void
    {
        $types->register(new PhpEnumType(FixtureStatus::class));
        $types->register(new PhpEnumType(MatchEventType::class));

        Event::listen(BuildExtensionsResponse::class, AddQueryMetricsExtension::class);

        DB::listen(fn (QueryExecuted $query) => $this->app->make(QueryMetrics::class)->record($query->time));
    }
}
