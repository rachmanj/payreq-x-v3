<?php

return [

    'similarity_threshold' => (float) env('NOTULEN_SIMILARITY_THRESHOLD', 0.22),

    'top_k' => (int) env('NOTULEN_TOP_K', 6),

    'chunk_size' => (int) env('NOTULEN_CHUNK_SIZE', 1500),

    'chunk_overlap' => (int) env('NOTULEN_CHUNK_OVERLAP', 200),

    'embed_batch_size' => (int) env('NOTULEN_EMBED_BATCH_SIZE', 20),

    'ocr_fallback_enabled' => (bool) env('NOTULEN_OCR_FALLBACK', true),

    'max_chunks_scanned' => (int) env('NOTULEN_MAX_CHUNKS_SCANNED', 5000),

    'chunk_cache_ttl' => (int) env('NOTULEN_CHUNK_CACHE_TTL', 300),

    'ocr_max_pages' => (int) env('NOTULEN_OCR_MAX_PAGES', 50),

    'ocr_max_mb' => (float) env('NOTULEN_OCR_MAX_MB', 20),

    'streaming_enabled' => (bool) env('NOTULEN_STREAMING_ENABLED', false),

];
