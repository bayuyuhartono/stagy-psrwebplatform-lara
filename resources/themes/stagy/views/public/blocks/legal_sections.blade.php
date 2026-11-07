@php($sections = collect($data['sections'] ?? [])->values()->map(fn (array $section, int $index): array => [...$section, 'number' => $index + 1, 'anchor' => 'bagian-'.($index + 1)]))
<section class="bg-public-mist px-4 pt-16 pb-12 sm:px-6">
    <div class="mx-auto flex max-w-290 flex-col gap-4">
        @if (filled($data['eyebrow'] ?? null))
            <span class="text-xs tracking-[0.08em] text-public-accent">{{ $data['eyebrow'] }}</span>
        @endif
        <h1 class="text-[clamp(1.875rem,4vw,2.75rem)] leading-[1.18] font-normal">{{ $data['heading'] ?? '' }}</h1>
        @if (filled($data['updated'] ?? null))
            <span class="text-[0.8125rem] text-public-muted">{{ $data['updated'] }}</span>
        @endif
    </div>
</section>
<div class="mx-auto flex max-w-290 flex-wrap items-start gap-14 px-4 pt-12 pb-22 sm:px-6">
    <nav aria-label="{{ __('theme::site.table_of_contents') }}" class="flex min-w-0 flex-[0_1_16.25rem] flex-col gap-1 lg:sticky lg:top-28">
        <span class="mb-2 text-xs tracking-[0.08em] text-public-accent">{{ __('theme::site.table_of_contents') }}</span>
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
</div>
