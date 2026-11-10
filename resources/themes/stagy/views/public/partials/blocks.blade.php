{{--
    Renders a page's blocks, telling each block how many blocks of its type came before it.
    `inline` (optional): a mid-article insert for the first rich text block, see posts/show.
--}}
@php($seen = [])
@foreach ($blocks as $block)
    @php($type = (string) ($block['type'] ?? ''))
    @php($seen[$type] = ($seen[$type] ?? -1) + 1)
    <x-public.block :block="$block" :media="$blockMedia ?? null" :position="$seen[$type]" :inline="$type === 'rich_text' && $seen[$type] === 0 ? ($inline ?? null) : null" />
@endforeach
