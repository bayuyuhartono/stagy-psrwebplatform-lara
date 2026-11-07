{{-- Stagy pages are composed entirely from content blocks; the page title only feeds the document title. --}}
<x-public.layout :page="$page" :settings="$settings" :menu="$menu" :meta-title="$page->title" :canonical="localized_route('website.pages.show', $page->slug)">
    @include('public.partials.blocks', ['blocks' => $page->blocks ?? []])
</x-public.layout>
