<?php

declare(strict_types=1);

use App\Providers\AppServiceProvider;
use Nuwave\Lighthouse\Subscriptions\SubscriptionServiceProvider;

return [
    AppServiceProvider::class,
    SubscriptionServiceProvider::class,
];
