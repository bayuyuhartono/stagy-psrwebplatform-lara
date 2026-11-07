@props(['tone' => 'accent'])

{{-- A small pill label. Position it (e.g. on a card edge) with classes from the caller. --}}
<span {{ $attributes->class([
    'rounded-full tracking-[0.06em]',
    'bg-public-accent px-3 py-1.5 text-[0.6875rem] text-white' => $tone === 'accent',
    'bg-public-mint px-3.5 py-2 text-xs text-public-deep' => $tone === 'mint',
    'bg-public-paper px-3 py-1.5 text-[0.6875rem] text-public-ink' => $tone === 'paper',
]) }}>{{ $slot }}</span>
