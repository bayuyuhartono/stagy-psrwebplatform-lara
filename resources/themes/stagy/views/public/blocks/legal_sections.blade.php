@php($sections = collect($data['sections'] ?? [])->values()->map(fn (array $section, int $index): array => [...$section, 'number' => $index + 1, 'anchor' => 'bagian-'.($index + 1)]))
<section class="bg-public-mist pt-16 pb-12">
    <x-public.container class="flex flex-col gap-4">
        @if (filled($data['eyebrow'] ?? null))
            <x-public.eyebrow>{{ $data['eyebrow'] }}</x-public.eyebrow>
        @endif
        <x-public.heading :level="1" size="title">{{ $data['heading'] ?? '' }}</x-public.heading>
        @if (filled($data['updated'] ?? null))
            <span class="text-[0.8125rem] text-public-muted">{{ $data['updated'] }}</span>
        @endif
    </x-public.container>
</section>
<x-public.container class="flex flex-wrap items-start gap-14 pt-12 pb-22">
    <nav aria-label="{{ __('theme::site.table_of_contents') }}" class="flex min-w-0 flex-[0_1_16.25rem] flex-col gap-1 lg:sticky lg:top-28">
        <x-public.eyebrow class="mb-2">{{ __('theme::site.table_of_contents') }}</x-public.eyebrow>
        @foreach ($sections as $section)
            <a href="#{{ $section['anchor'] }}" class="py-2 text-[0.8125rem] leading-[1.5] text-public-ink hover:text-public-accent">{{ $section['number'] }}. {{ $section['title'] ?? '' }}</a>
        @endforeach
    </nav>
    <article class="flex min-w-0 max-w-180 flex-[1_1_30rem] flex-col gap-10">
        @if (filled($data['intro'] ?? null))
            <p class="leading-[1.9] whitespace-pre-line text-public-muted">{{ $data['intro'] }}</p>
        @endif
        @foreach ($sections as $section)
            <section id="{{ $section['anchor'] }}" class="flex flex-col gap-3">
                <h2 class="text-xl leading-[1.35] font-normal"><span class="text-public-accent">{{ $section['number'] }}.</span> {{ $section['title'] ?? '' }}</h2>
                <p class="text-sm leading-[1.9] whitespace-pre-line text-public-muted">{{ $section['body'] ?? '' }}</p>
            </section>
        @endforeach
        @if (filled($data['closing'] ?? null))
            <p class="rounded-2xl bg-public-mist p-5 text-[0.8125rem] leading-[1.8] whitespace-pre-line text-public-muted">{{ $data['closing'] }}</p>
        @endif
    </article>
</x-public.container>
