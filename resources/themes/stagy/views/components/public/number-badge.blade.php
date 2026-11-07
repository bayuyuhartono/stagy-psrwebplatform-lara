@props(['number', 'tone' => 'mint', 'size' => 'md'])

{{-- A two-digit step or card number in a rounded square: "01", "02", … --}}
<span {{ $attributes->class([
    'grid shrink-0 place-items-center',
    'size-11 rounded-xl text-sm' => $size === 'md',
    'size-8 rounded-lg text-xs' => $size === 'sm',
    'bg-public-mint text-public-accent' => $tone === 'mint',
    'bg-public-ink text-white' => $tone === 'ink',
    'bg-public-accent text-white' => $tone === 'accent',
]) }}>{{ sprintf('%02d', $number) }}</span>
