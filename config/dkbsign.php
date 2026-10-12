<?php

return [

    'base_url' => env('DKBSIGN_BASE_URL', 'https://api.dkbsigns.com'),

    'token' => env('DKBSIGN_TOKEN'),

    'timeout' => (int) env('DKBSIGN_TIMEOUT', 120),

];
