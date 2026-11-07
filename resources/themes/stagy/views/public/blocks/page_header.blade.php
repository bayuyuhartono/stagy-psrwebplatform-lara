<x-public.container as="section" class="flex flex-col gap-5 pt-18 pb-10">
    @if (filled($data['eyebrow'] ?? null))
        <x-public.eyebrow>{{ $data['eyebrow'] }}</x-public.eyebrow>
    @endif
    <x-public.heading :level="1" size="display" :text="$data['heading'] ?? ''" class="max-w-225" />
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
</x-public.container>
