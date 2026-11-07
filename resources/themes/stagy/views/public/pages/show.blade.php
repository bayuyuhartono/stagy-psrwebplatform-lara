{{-- Stagy pages are composed entirely from content blocks; the page title only feeds the document title. --}}
<x-public.layout :page="$page" :settings="$settings" :menu="$menu" :meta-title="$page->title" :canonical="localized_route('website.pages.show', $page->slug)">
    @foreach ($page->blocks ?? [] as $block)
        <x-public.block :block="$block" :media="$blockMedia ?? null" />
    @endforeach
</x-public.layout>
