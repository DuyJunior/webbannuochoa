<?php

namespace App\Services;

use App\Models\Perfume;
use Illuminate\Support\Str;

final class FragranceEditorialService
{
    /**
     * Return a source-backed profile only for the exact catalog variant.
     * A reused slug alone must never attach one perfume's notes to another.
     */
    public static function forPerfume(Perfume $perfume): array
    {
        $name = self::normalize((string) $perfume->name);
        $slug = self::normalize((string) $perfume->slug);
        $brand = self::normalize((string) $perfume->brand);
        $concentration = self::concentration((string) $perfume->concentration);

        foreach (config('fragrance-editorial.profiles', []) as $key => $profile) {
            if (! in_array($name, array_map(self::normalize(...), $profile['names']), true)
                || ! in_array($brand, array_map(self::normalize(...), $profile['brands']), true)
                || ! in_array($concentration, $profile['concentrations'], true)
                || ($slug !== '' && ! in_array($slug, array_map(self::normalize(...), $profile['slugs']), true))) {
                continue;
            }

            return self::localize([
                'verified' => true,
                'profile_key' => $key,
                'mode' => $profile['mode'],
                'layers' => $profile['layers'] ?? [],
                'key_notes' => $profile['key_notes'],
                'family' => $profile['family'],
                'moods' => $profile['moods'],
                'mood_reason' => $profile['mood_reason'],
                'story' => $profile['story'],
                'occasion' => $profile['occasion'],
                'sources' => $profile['sources'],
                'reviewed_at' => config('fragrance-editorial.reviewed_at'),
                'note_label' => $profile['mode'] === 'pyramid' ? 'Ba tầng hương' : 'Những nốt hương nổi bật',
            ]);
        }

        return self::localize([
            'verified' => false,
            'profile_key' => null,
            'mode' => 'pending',
            'layers' => [],
            'key_notes' => [],
            'family' => '',
            'moods' => [],
            'mood_reason' => [],
            'story' => 'Soopi đang bổ sung thông tin nốt hương đã đối chiếu cho phiên bản này.',
            'occasion' => 'Hãy trao đổi với Soopi về mùi hương bạn thích và thử trên da trước khi chọn.',
            'sources' => [],
            'reviewed_at' => null,
            'note_label' => 'Cùng tìm hiểu mùi hương',
        ]);
    }

    /** Translate editorial copy only; catalog matching and mood identifiers stay stable. */
    private static function localize(array $profile): array
    {
        foreach (['family', 'story', 'occasion', 'note_label'] as $field) {
            $profile[$field] = __($profile[$field]);
        }
        $profile['key_notes'] = array_map(fn ($note) => __($note), $profile['key_notes']);
        $profile['mood_reason'] = array_map(fn ($reason) => __($reason), $profile['mood_reason']);
        foreach ($profile['layers'] as &$layer) {
            $layer['label'] = __($layer['label']);
            $layer['description'] = __($layer['description']);
            $layer['notes'] = array_map(fn ($note) => __($note), $layer['notes']);
        }

        return $profile;
    }

    private static function normalize(string $value): string
    {
        return Str::slug(trim($value));
    }

    private static function concentration(string $value): string
    {
        return match (self::normalize($value)) {
            'edp', 'eau-de-parfum' => 'edp',
            'edt', 'eau-de-toilette' => 'edt',
            'parfum' => 'parfum',
            'extrait', 'extrait-de-parfum', 'parfum-extrait' => 'extrait',
            default => '',
        };
    }
}
