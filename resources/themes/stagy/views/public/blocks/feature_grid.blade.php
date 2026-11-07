<x-public.container as="section" class="grid grid-cols-[repeat(auto-fit,minmax(15rem,1fr))] gap-4 py-12">
    @foreach ($data['items'] ?? [] as $item)
        <article class="flex flex-col gap-3.5 rounded-[1.125rem] border border-public-line bg-public-paper px-6 py-6.5">
            <x-public.number-badge :number="$loop->iteration" />
            <h3 class="text-base leading-[1.4] font-normal">{{ $item['title'] ?? '' }}</h3>
            @if (filled($item['description'] ?? null))
                <p class="text-[0.8125rem] leading-[1.75] text-public-muted">{{ $item['description'] }}</p>
            @endif
        </article>
    @endforeach
</x-public.container>
