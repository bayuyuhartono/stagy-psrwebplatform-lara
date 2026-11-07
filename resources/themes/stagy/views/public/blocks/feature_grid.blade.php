<section class="mx-auto grid max-w-290 grid-cols-[repeat(auto-fit,minmax(15rem,1fr))] gap-4 px-4 py-12 sm:px-6">
    @foreach ($data['items'] ?? [] as $item)
        <article class="flex flex-col gap-3.5 rounded-[1.125rem] border border-public-line bg-public-paper px-6 py-6.5">
            <span class="grid size-11 place-items-center rounded-xl bg-public-mint text-sm text-public-accent">{{ str_pad((string) $loop->iteration, 2, '0', STR_PAD_LEFT) }}</span>
            <h3 class="text-base leading-[1.4] font-normal">{{ $item['title'] ?? '' }}</h3>
            @if (filled($item['description'] ?? null))
                <p class="text-[0.8125rem] leading-[1.75] text-public-muted">{{ $item['description'] }}</p>
            @endif
        </article>
    @endforeach
</section>
