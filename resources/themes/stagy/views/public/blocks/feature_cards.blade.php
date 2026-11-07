@inject('links', 'App\Features\ContentBlocks\Services\ContentLinkResolver')
<section class="bg-public-mist px-4 py-22 sm:px-6">
    <div class="mx-auto flex max-w-290 flex-col gap-10">
        <x-public.section-heading :eyebrow="$data['eyebrow'] ?? null" :heading="$data['heading'] ?? null" :link="$data['link'] ?? null" />
        <div class="grid grid-cols-[repeat(auto-fit,minmax(15rem,1fr))] gap-4">
            @foreach ($data['items'] ?? [] as $item)
                @php($href = $links->resolve($item['url'] ?? null))
                <{{ $href ? 'a' : 'div' }} @if ($href) href="{{ $href }}" @endif @class([
                    'flex flex-col gap-3.5 rounded-[1.125rem] border border-public-line bg-public-paper px-6 py-6.5 text-public-ink',
                    'transition hover:border-public-accent' => $href,
                ])>
                    <span class="grid size-11 place-items-center rounded-xl bg-public-mint text-sm text-public-accent">{{ str_pad((string) $loop->iteration, 2, '0', STR_PAD_LEFT) }}</span>
                    <span class="text-base leading-[1.4]">{{ $item['title'] ?? '' }}</span>
                    @if (filled($item['description'] ?? null))
                        <span class="text-[0.8125rem] leading-[1.75] text-public-muted">{{ $item['description'] }}</span>
                    @endif
                </{{ $href ? 'a' : 'div' }}>
            @endforeach
        </div>
    </div>
</section>
