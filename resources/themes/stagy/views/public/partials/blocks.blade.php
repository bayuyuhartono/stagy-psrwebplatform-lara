{{-- Renders a page's blocks, telling each block how many blocks of its type came before it. --}}
@php($seen = [])
@foreach ($blocks as $block)
    @php($type = (string) ($block['type'] ?? ''))
    @php($seen[$type] = ($seen[$type] ?? -1) + 1)
    <x-public.block :block="$block" :media="$blockMedia ?? null" :position="$seen[$type]" />
@endforeach
