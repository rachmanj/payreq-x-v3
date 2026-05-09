<?php

return [

    'similarity_threshold' => (float) env('HELP_SIMILARITY_THRESHOLD', 0.22),

    'top_k' => (int) env('HELP_TOP_K', 6),

    'reindex_batch_size' => (int) env('HELP_REINDEX_BATCH_SIZE', 20),

    'manuals_path' => base_path('docs/manuals'),

    'navigation_json_path' => base_path('docs/help-navigation.json'),

    'feedback_notify_email' => env('HELP_FEEDBACK_NOTIFY_EMAIL'),

    'locale_boost_match' => 0.02,

    'locale_boost_both' => 0.01,

];
