@props(['block', 'media' => null, 'position' => 0, 'inline' => null])

{{-- Dispatches each content block to its partial in public/blocks. `position` counts earlier blocks of the same type on the page. --}}
@php($type = $block['type'] ?? null)
@if (is_string($type) && view()->exists("public.blocks.{$type}"))
    @include("public.blocks.{$type}", [
        'data' => $block['data'] ?? [],
        'blockMedia' => ($media ?? collect())->get($block['data']['media_id'] ?? ''),
        'position' => $position,
        'inline' => $inline,
    ])
@endif
