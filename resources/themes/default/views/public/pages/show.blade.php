<x-public.layout :page="$page" :settings="$settings" :menu="$menu" :canonical="route('website.pages.show', $page->slug)">
    <section class="relative overflow-hidden bg-public-ink pb-24 pt-40 text-white lg:pb-32 lg:pt-52">
        <div class="absolute inset-0 bg-[radial-gradient(ellipse_at_top_right,var(--color-public-surface),transparent_50%)] opacity-40"></div>
        <div class="relative mx-auto max-w-4xl px-5 text-center sm:px-8 lg:px-10">
            <h1 class="public-reveal text-5xl font-semibold leading-[0.9] tracking-[-0.05em] sm:text-6xl lg:text-8xl">{{ $page->title }}</h1>
        </div>
    </section>

    @foreach (collect($page->blocks ?? [])->reject(fn ($block) => ($block['type'] ?? null) === 'hero') as $block)
        <x-public.block :block="$block" :media="$blockMedia ?? null" />
    @endforeach
</x-public.layout>
