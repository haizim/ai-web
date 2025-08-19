<?php
namespace App\Services;

use GuzzleHttp\Client;

class Gemini
{
    public static function send($chats, $system = null, $thinking = false)
    {
        $model = config('ai.gemini.model');
        $key = config('ai.gemini.key');
        $url = "https://generativelanguage.googleapis.com/v1beta/models/$model:generateContent?key=$key";

        $head = [
            'Content-Type' => 'application/json'
        ];

        $data = [
            'contents' => $chats
        ];

        if ($thinking) {
            $data['generationConfig'] = [
                'temperature' => 1,
                'thinkingConfig' => [
                    'thinkingBudget' => -1
                ]
            ];
        }

        if (!empty($system)) $data['system_instruction'] = [
            'parts' => [[ 'text' => $system ]]
        ];

        $client = new Client();
        // try {
            $response = $client->post($url, [
                'headers' => $head,
                'json' => $data
            ]);
        // } catch (\Exception $e) {
        //     return false;
        // }
        if ($response->getStatusCode() == 200) {
            $result = json_decode((string)$response->getBody(), true);
            // var_dump($result);
            // dd($chats, $result);
            return [
                'response' => $result['candidates'][0]['content']['parts'][0]['text'],
                'token' => $result['usageMetadata']['promptTokenCount']
                // 'token' => $result['usageMetadata']['totalTokenCount']
            ];
        }
    }

    public static function generate($prompt, $system = null, $thinking = false)
    {
        $chats = [
            [
                'parts' => [
                    'text' => $prompt
                ]
            ]
        ];
        return self::send($chats, $system, $thinking);
    }
}