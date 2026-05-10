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
    'openrouter' => [
        'key' => env('OPENROUTER_KEY'),
        'model' => env('OPENROUTER_MODEL', 'google/gemini-2.5-flash'),
        'models' => [
            'generate_page' => env('AI_MODEL_GENERATE_PAGE'),
            'edit_page' => env('AI_MODEL_EDIT_PAGE'),
            'generate_style' => env('AI_MODEL_GENERATE_STYLE', 'z-ai/glm-4.7'),
            'generate_style_from_desc' => env('AI_MODEL_GENERATE_STYLE_FROM_DESC', 'x-ai/grok-code-fast-1'),
            'generate_miniapp' => env('AI_MODEL_GENERATE_MINIAPP'),
            'edit_miniapp' => env('AI_MODEL_EDIT_MINIAPP'),
            'generate_functionality' => env('AI_MODEL_GENERATE_FUNCTIONALITY'),
            'generate_miniapp_style' => env('AI_MODEL_GENERATE_MINIAPP_STYLE'),
        ],
    ],
    'provider' => env('AI_PROVIDER', 'gemini'),
    'default_prompt_rangkuman' => env('AI_DEFAULT_PROMPT_RANGKUMAN', 'Buatkan rangkuman dari data yang ada'),
];
