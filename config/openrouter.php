<?php
return [
    'key' => env('OPENROUTER_API_KEY'),
    'model' => env('OPENROUTER_MODEL', 'openai/gpt-4.1-mini'),
    'base_url' => env('OPENROUTER_BASE_URL', 'https://openrouter.ai/api/v1'),
];
