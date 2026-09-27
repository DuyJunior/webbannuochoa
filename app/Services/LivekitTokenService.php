<?php

namespace App\Services;

use App\Models\Livestream;
use Illuminate\Support\Str;

class LivekitTokenService
{
    public function configured(): bool
    {
        return (bool) (config('services.livekit.url') && config('services.livekit.key') && config('services.livekit.secret'));
    }

    public function url(): string
    {
        return (string) config('services.livekit.url');
    }

    public function token(Livestream $livestream, bool $canPublish, string $identity): string
    {
        $now = time();
        $header = ['alg' => 'HS256', 'typ' => 'JWT'];
        $claims = [
            'iss' => config('services.livekit.key'),
            'sub' => $identity,
            'jti' => (string) Str::uuid(),
            'iat' => $now,
            'nbf' => $now,
            'exp' => $now + 3600,
            'video' => [
                'room' => 'ha-thu-live-'.$livestream->id,
                'roomJoin' => true,
                'canPublish' => $canPublish,
                'canPublishSources' => $canPublish ? ['camera', 'microphone'] : [],
                'canSubscribe' => true,
                'canPublishData' => false,
            ],
        ];

        $parts = [
            $this->encode($header),
            $this->encode($claims),
        ];
        $signature = hash_hmac('sha256', implode('.', $parts), (string) config('services.livekit.secret'), true);
        $parts[] = rtrim(strtr(base64_encode($signature), '+/', '-_'), '=');

        return implode('.', $parts);
    }

    private function encode(array $value): string
    {
        return rtrim(strtr(base64_encode(json_encode($value, JSON_THROW_ON_ERROR)), '+/', '-_'), '=');
    }
}
