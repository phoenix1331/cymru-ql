<?php

declare(strict_types=1);

return [

    /*
    | Directory holding the committed seed data (leagues, seasons, grounds,
    | clubs, club seasons and the distance matrix) as JSON files.
    */
    'data_path' => env('CYMRU_DATA_PATH', database_path('data')),

];
