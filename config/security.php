<?php

return [

    'login' => [
        'max_attempts_per_minute' => (int) env('LOGIN_MAX_ATTEMPTS_PER_MINUTE', 10),
        'decay_minutes' => (int) env('LOGIN_RATE_LIMIT_DECAY_MINUTES', 1),
    ],

];
