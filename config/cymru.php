<?php

declare(strict_types=1);

return [

    /*
    | Directory holding the committed seed data (leagues, seasons, grounds,
    | clubs, club seasons and the distance matrix) as JSON files.
    */
    'data_path' => env('CYMRU_DATA_PATH', database_path('data')),

    /*
    | Over the Terraces Cymru config (src/data/cymru.js), read by
    | `php artisan cymru:import`. Only needed to regenerate the seed data.
    */
    'ott_config_path' => env('OTT_CYMRU_PATH'),

    /*
    | Node binary used to evaluate the Over the Terraces config module.
    */
    'node_binary' => env('NODE_BINARY', 'node'),

    /*
    | User agent sent to Wikidata and Nominatim by `cymru:import --enrich`.
    | Both services ask for an identifying user agent; set your own here.
    */
    'http_user_agent' => env('CYMRU_HTTP_USER_AGENT', 'laravel-data-import/1.0'),

];
