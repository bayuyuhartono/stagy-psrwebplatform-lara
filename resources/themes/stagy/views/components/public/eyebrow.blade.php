@props(['tone' => 'accent'])

{{-- The small label above a heading. "sage" is for dark (navy) backgrounds. --}}
<span {{ $attributes->class([
    'text-xs tracking-[0.08em]',
    'text-public-accent' => $tone === 'accent',
    'text-public-sage' => $tone === 'sage',
]) }}>{{ $slot }}</span>
