<?php

namespace App\Services;

use GuzzleHttp\Client; // Pastikan Guzzle di-import
use GuzzleHttp\Exception\RequestException;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;

class OpenRouter
{
    public static function send(array $chats, ?string $system = null, string $model = ''): array
    {
        ini_set('max_execution_time', '6000');

        if (empty($model)) {
            $model = config('ai.openrouter.model');
        }
        
        $client = new Client();
        $apiKey = config('ai.openrouter.key');
        $baseUrl = 'https://openrouter.ai/api/v1';

        if (empty($apiKey) || empty($baseUrl)) {
            Log::error('OpenRoute configuration (ai.openrouter.key or ai.openrouter.url) not set.');
            return ['result' => 'OpenRoute configuration (ai.openrouter.key or ai.openrouter.url) not set.', 'token' => 0];
        }

        // $messages = self::fromGemini($chats);
        $messages = $chats;
        // return $messages;
        if ($system) {
            array_unshift($messages, [
                'role' => 'system',
                'content' => $system
            ]);
        }

        $payload = [
            'model' => $model,
            'messages' => $messages,
        ];
        // return ['response' => $payload];

        $response = $client->post($baseUrl . '/chat/completions', [
            'headers' => [
                'Authorization' => 'Bearer ' . $apiKey,
                'Content-Type' => 'application/json',
            ],
            'json' => $payload,
        ]);

        // return $response;

        $data = json_decode($response->getBody()->getContents(), true);

        $resultText = $data['choices'][0]['message']['content'] ?? null;
        $tokenUsage = $data['usage'] ? $data['usage']['total_tokens'] : null;

        return [
            'model' => $data['model'],
            'response' => $resultText,
            'token' => $tokenUsage,
            'data' => $data
        ];
    }

    public static function fromGemini(array $chats): array
    {
        $convertedMessages = [];
        
        foreach ($chats as $message) {
            $role = $message['role'] === 'model' ? 'assistant' : $message['role'];
            $content = $message['parts'][0]['text'] ?? '';

            $convertedMessages[] = [
                'role' => $role,
                'content' => $content,
            ];
        }

        return $convertedMessages;
    }

    public static function getModels(): array
    {
        $cacheKey = 'openrouter_models_list';
        // Durasi cache (misalnya: 24 jam)
        $cacheDuration = now()->addHours(24);

        // 1. Cek cache
        if (Cache::has($cacheKey)) {
            return Cache::get($cacheKey);
        }

        // 2. Jika cache tidak ada, ambil dari API
        $client = new Client();
        $baseUrl = 'https://openrouter.ai/api/v1';
        $apiKey = config('ai.openrouter.key'); // Diperlukan untuk rate limit

        if (empty($baseUrl) || empty($apiKey)) {
            Log::error('OpenRoute configuration (ai.openrouter.key or ai.openrouter.url) not set.');
            return [];
        }

        // try {
            // Endpoint untuk model adalah GET /models
            $response = $client->get($baseUrl . '/models', [
                'headers' => [
                    'Authorization' => 'Bearer ' . $apiKey,
                    'Accept' => 'application/json',
                ],
            ]);

            $data = json_decode($response->getBody()->getContents(), true);

            // 3. Transformasi data ke format yang diminta
            // Respons OpenRouter ada di dalam kunci 'data'
            $formattedModels = collect($data['data'] ?? [])
                ->mapWithKeys(function ($model) {
                    // Hanya ambil jika ID dan Name ada
                    if (isset($model['id']) && isset($model['name'])) {
                        return [$model['id'] => $model['name'] . ' ($' . (floatval($model['pricing']['completion']) * 1000000) . '/M) (' . number_format($model['context_length']) . ' T)'];
                    }
                    return [];
                })
                ->all();
            
            if (empty($formattedModels)) {
                Log::warning('OpenRoute getModels: No models found or response format mismatch.');
                return [];
            }

            // 4. Simpan ke cache
            Cache::put($cacheKey, $formattedModels, $cacheDuration);

            return $formattedModels;

        // } catch (RequestException $e) {
        //     Log::error('OpenRoute API (Get Models) Error: ' . $e->getMessage());
        //     return [];
        // }
    }

    public static function generate($prompt, $system = null, $model = '')
    {
        $chats = [
            [
                'role' => 'user',
                'content' => $prompt
            ]
        ];
        
        return self::send($chats, $system, $model);
    }
}