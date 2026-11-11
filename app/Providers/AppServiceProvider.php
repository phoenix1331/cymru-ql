<?php

declare(strict_types=1);

namespace App\Providers;

use App\Data\DataFiles;
use App\Import\Enrichment\NominatimClient;
use App\Import\Enrichment\WikidataClient;
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
