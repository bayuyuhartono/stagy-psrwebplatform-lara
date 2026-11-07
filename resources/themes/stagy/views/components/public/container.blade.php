@props(['as' => 'div', 'width' => 'page'])

{{--
    The column every Stagy section sits in, with a 16/24px gutter.
      page    – 1160px, the default
      article – 900px, article headers
      media   – 960px, standalone images
      text    – 720px, reading width
--}}
<{{ $as }} {{ $attributes->class([
    'mx-auto w-full px-4 sm:px-6',
    'max-w-290' => $width === 'page',
    'max-w-225' => $width === 'article',
    'max-w-240' => $width === 'media',
    'max-w-180' => $width === 'text',
]) }}>{{ $slot }}</{{ $as }}>
