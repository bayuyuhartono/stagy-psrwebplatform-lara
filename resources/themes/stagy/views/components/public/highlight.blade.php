@props(['text' => '', 'tone' => 'text-public-accent'])
{{-- Escapes the text, then turns *word* into a highlighted span. --}}
{!! preg_replace('/\*([^*]+)\*/', '<span class="'.e($tone).'">$1</span>', e((string) $text)) !!}
