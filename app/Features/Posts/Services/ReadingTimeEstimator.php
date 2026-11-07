<?php

namespace App\Features\Posts\Services;

use App\Models\Post;

/**
 * Estimates how long an article takes to read from the text in its excerpt
 * and content blocks, at an unhurried 200 words per minute.
 */
class ReadingTimeEstimator
{
    private const WORDS_PER_MINUTE = 200;

    public function minutes(Post $post): int
    {
        $words = str_word_count(strip_tags($this->text($post->excerpt).' '.$this->text($post->blocks ?? [])));

        return max(1, (int) ceil($words / self::WORDS_PER_MINUTE));
    }

    /**
     * Every string value in a block payload, joined. Keys such as ids, types
     * and URLs are skipped so they do not count as words.
     */
    private function text(mixed $value, ?string $key = null): string
    {
        if (is_array($value)) {
            return implode(' ', array_map(fn (mixed $item, int|string $itemKey): string => $this->text($item, (string) $itemKey), $value, array_keys($value)));
        }

        if (! is_string($value) || in_array($key, ['id', 'type', 'url', 'media_id', 'anchor', 'variant', 'visual'], true)) {
            return '';
        }

        return $value;
    }
}
