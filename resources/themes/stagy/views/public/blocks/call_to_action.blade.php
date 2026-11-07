<section class="px-4 pb-22 sm:px-6">
    <div class="mx-auto flex max-w-290 flex-wrap items-center justify-between gap-x-10 gap-y-7 rounded-[1.75rem] bg-public-ink px-[clamp(1.5rem,5vw,4rem)] py-14 text-white">
        <div class="flex min-w-0 flex-[1_1_26.25rem] flex-col gap-3.5">
            <h2 class="text-[clamp(1.5rem,2.8vw,2.125rem)] leading-[1.25] font-normal"><x-public.highlight :text="$data['heading'] ?? ''" tone="text-public-sage" /></h2>
            @if (filled($data['body'] ?? null))
                <p class="text-sm leading-[1.8] text-public-on-ink">{{ $data['body'] }}</p>
            @endif
        </div>
        <div class="flex flex-wrap gap-3">
            <x-public.button :href="$data['url'] ?? null" variant="light">{{ $data['label'] ?? '' }}</x-public.button>
            @if (filled($data['secondary_action']['url'] ?? null))
                <x-public.button :href="$data['secondary_action']['url']" variant="accent">{{ $data['secondary_action']['label'] ?? '' }}</x-public.button>
            @endif
        </div>
    </div>
</section>
