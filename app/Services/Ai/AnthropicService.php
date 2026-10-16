<?php

namespace App\Services\Ai;

use Illuminate\Support\Facades\Http;
use RuntimeException;

class AnthropicService
{
    public function ask(string $prompt): string
    {
        $apiKey = config('services.anthropic.key');
        $model = config('services.anthropic.model');

        if (! $apiKey) {
            throw new RuntimeException('Anthropic API key is not configured.');
        }

        $response = Http::baseUrl('https://api.anthropic.com/v1')
            ->withHeaders([
                'x-api-key' => $apiKey,
                'anthropic-version' => '2023-06-01',
            ])
            ->post('/messages', [
                'model' => $model,
                'max_tokens' => 500,
                'messages' => [
                    [
                        'role' => 'user',
                        'content' => $prompt,
                    ],
                ],
            ])
            ->throw()
            ->json();

        return data_get($response, 'content.0.text', '');
    }
}
