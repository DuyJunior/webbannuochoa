<?php

namespace App\Support;

use App\Rules\SafeVideoUrl;

class VideoUrl
{
    public static function embed(?string $value): ?string
    {
        $url = trim($value ?? '');
        if (! SafeVideoUrl::accepts($url)) {
            return null;
        }
        $host = strtolower((string) parse_url($url, PHP_URL_HOST));
        if ($host === '') {
            return asset(ltrim($url, '/'));
        }
        $path = (string) parse_url($url, PHP_URL_PATH);
        $id = null;
        if (in_array($host, ['youtube.com', 'www.youtube.com', 'm.youtube.com', 'youtube-nocookie.com', 'www.youtube-nocookie.com'], true)) {
            if ($path === '/watch') {
                parse_str((string) parse_url($url, PHP_URL_QUERY), $query);
                $id = $query['v'] ?? null;
            } elseif (preg_match('~^/(?:shorts|embed|live)/([\w-]+)~', $path, $match)) {
                $id = $match[1];
            }
        } elseif (in_array($host, ['youtu.be', 'www.youtu.be'], true)) {
            $id = ltrim($path, '/');
        }
        if (is_string($id) && preg_match('/^[a-zA-Z0-9_-]+$/', $id)) {
            return 'https://www.youtube.com/embed/'.$id.'?autoplay=1&rel=0';
        }
        if (in_array($host, ['tiktok.com', 'www.tiktok.com', 'm.tiktok.com'], true)
            && preg_match('~^/@[^/]+/video/(\d+)~', $path, $match)) {
            return 'https://www.tiktok.com/player/v1/'.$match[1];
        }

        return $url;
    }
}
