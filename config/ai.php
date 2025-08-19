<?php

return [
    'gemini' => [
        'key' => env('GEMINI_KEY'),
        'model' => env('GEMINI_MODEL', 'gemini-2.0-flash'),
    ],
    'gpt' => [
        'key' => env('GPT_KEY'),
        'model' => env('GPT_MODEL', 'gpt-4.1-mini'),
    ],
    'provider' => env('AI_PROVIDER', 'gemini'),
    'default_prompt_rangkuman' => env('AI_DEFAULT_PROMPT_RANGKUMAN', 'Buatkan rangkuman dari data yang ada'),
];
