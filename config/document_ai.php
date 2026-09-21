<?php

return [
    // Layanan FastAPI internal. Tidak menggunakan OpenRouter atau API eksternal.
    'url' => rtrim(env('DOCUMENT_AI_URL', 'http://127.0.0.1:8010'), '/'),
    'token' => env('DOCUMENT_AI_TOKEN'),
    'timeout' => (int) env('DOCUMENT_AI_TIMEOUT', 20),
];
