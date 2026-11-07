@props(['level' => 2, 'size' => 'section', 'text' => null, 'tone' => 'text-public-accent'])

{{--
    The Stagy type scale. Pass `text` to get *highlight* markup, or use the slot.
      display    – page and hero titles
      title      – titles beside a form or list (FAQ, legal, demo, article, 404)
      section    – section headings
      subsection – headings inside a section (feature groups, featured article)
--}}
@php($sizes = [
    'display' => 'text-[clamp(1.875rem,4vw,3rem)] leading-[1.15]',
    'title' => 'text-[clamp(1.875rem,4vw,2.75rem)] leading-[1.18]',
    'section' => 'text-[clamp(1.5rem,2.8vw,2.125rem)] leading-[1.25]',
    'subsection' => 'text-[clamp(1.375rem,2.6vw,1.875rem)] leading-[1.25]',
])

<h{{ $level }} {{ $attributes->class([$sizes[$size] ?? $sizes['section'], 'font-normal']) }}>@if ($text !== null)<x-public.highlight :text="$text" :tone="$tone" />@else{{ $slot }}@endif</h{{ $level }}>
