<?php

return [

    'similarity_threshold' => (float) env('NOTULEN_SIMILARITY_THRESHOLD', 0.22),

    'top_k' => (int) env('NOTULEN_TOP_K', 6),

    'chunk_size' => (int) env('NOTULEN_CHUNK_SIZE', 1500),

    'chunk_overlap' => (int) env('NOTULEN_CHUNK_OVERLAP', 200),

    'embed_batch_size' => (int) env('NOTULEN_EMBED_BATCH_SIZE', 20),

    'ocr_fallback_enabled' => (bool) env('NOTULEN_OCR_FALLBACK', true),

];
