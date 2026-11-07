<section class="mx-auto flex max-w-290 flex-col gap-5 px-4 pt-18 pb-10 sm:px-6">
    @if (filled($data['eyebrow'] ?? null))
        <span class="text-xs tracking-[0.08em] text-public-accent">{{ $data['eyebrow'] }}</span>
    @endif
    <h1 class="max-w-225 text-[clamp(1.875rem,4vw,3rem)] leading-[1.15] font-normal"><x-public.highlight :text="$data['heading'] ?? ''" /></h1>
    @if (filled($data['intro'] ?? null))
        <p class="max-w-155 leading-[1.8] text-public-muted">{{ $data['intro'] }}</p>
    @endif
    @if (filled($data['links'] ?? []))
        @inject('links', 'App\Features\ContentBlocks\Services\ContentLinkResolver')
        <nav aria-label="{{ $data['eyebrow'] ?? $data['heading'] ?? '' }}" class="mt-2 flex flex-wrap gap-2">
            @foreach ($data['links'] as $link)
                @if ($href = $links->resolve($link['url'] ?? null))
                    <a href="{{ $href }}" class="inline-flex min-h-11 items-center rounded-full bg-public-mint px-4 text-[0.8125rem] tracking-[0.04em] text-public-ink transition hover:bg-public-sage">{{ $link['label'] ?? '' }}</a>
                @endif
            @endforeach
        </nav>
    @endif
</section>
