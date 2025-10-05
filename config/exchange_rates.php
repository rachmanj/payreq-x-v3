<?php

return [
    // Comma-separated in .env, e.g. EXCHANGE_RATES_TARGET="USD,AUD,SGD"
    'target_currencies' => collect(explode(',', env('EXCHANGE_RATES_TARGET', 'USD,AUD,SGD')))
        ->map(fn($c) => strtoupper(trim($c)))
        ->filter()
        ->values()
        ->all(),
];
