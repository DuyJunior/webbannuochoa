<?php

namespace App\Models\Concerns;

trait HasLocalizedContent
{
    public function getLocalizedNameAttribute(): string
    {
        return $this->localizedContent('name');
    }

    public function getLocalizedDescriptionAttribute(): string
    {
        return $this->localizedContent('description');
    }

    public function getLocalizedTitleAttribute(): string
    {
        return $this->localizedContent('title');
    }

    public function getLocalizedExcerptAttribute(): string
    {
        return $this->localizedContent('excerpt');
    }

    public function getLocalizedBodyAttribute(): string
    {
        return $this->localizedContent('body');
    }

    private function localizedContent(string $field): string
    {
        $original = (string) $this->getAttribute($field);
        if (app()->getLocale() !== 'en') {
            return $original;
        }
        $english = trim((string) $this->getAttribute($field.'_en'));

        // Exact source copy is used as fallback, so edits cannot show an old translation for the same slug.
        return $english !== '' ? $english : __(str_replace(["\r\n", "\r"], "\n", $original));
    }
}
