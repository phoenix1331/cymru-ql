<?php

declare(strict_types=1);

namespace App\Providers;

use App\Data\DataFiles;
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
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}
