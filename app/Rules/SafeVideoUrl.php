<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class SafeVideoUrl implements ValidationRule
{
    public static function accepts(string $value): bool
    {
        $remote = filter_var($value, FILTER_VALIDATE_URL)
            && in_array(strtolower((string) parse_url($value, PHP_URL_SCHEME)), ['http', 'https'], true);
        $local = preg_match('~^/?(?:videos|storage|images)/[a-zA-Z0-9/._-]+\.(?:mp4|webm|ogg)$~i', $value)
            && ! str_contains($value, '..');

        return $remote || $local;
    }

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! is_string($value) || ! self::accepts($value)) {
            $fail('Nhập liên kết HTTP/HTTPS hợp lệ hoặc đường dẫn video trong thư mục videos, storage, images.');
        }
    }
}
