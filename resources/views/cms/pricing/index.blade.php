<x-cms.app-layout title="Pricing" section="pricing">
    <section
        data-pricing-manager
        data-can-create="{{ auth()->user()->hasPermission('pricing.create') ? 'true' : 'false' }}"
        data-can-update="{{ auth()->user()->hasPermission('pricing.update') ? 'true' : 'false' }}"
        data-can-delete="{{ auth()->user()->hasPermission('pricing.delete') ? 'true' : 'false' }}"
        class="grid gap-6"
    >
        <div class="flex flex-col justify-between gap-4 sm:flex-row sm:items-end">
            <div>
                <p class="text-sm text-cms-navy/55">Plans, the comparison table and add-ons shown by the pricing blocks.</p>
                <h2 class="mt-1 text-2xl font-bold tracking-tight text-cms-navy">Pricing</h2>
            </div>
            <label class="grid gap-1.5">
                <span class="text-xs font-semibold text-cms-navy/60">Language</span>
                <select data-pricing-locale class="h-11 rounded-xl border border-cms-navy/10 bg-white px-3 text-sm font-semibold text-cms-navy outline-none focus:border-cms-gold">
                    @foreach (app(\App\Features\Localization\Services\LocaleRegistry::class)->options() as $code => $locale)
                        <option value="{{ $code }}">{{ $locale['name'] }}</option>
                    @endforeach
                </select>
            </label>
        </div>

        <div data-pricing-notice class="hidden rounded-xl border p-4 text-sm"></div>

        @foreach ([
            ['plans', 'Plans', 'Each plan becomes a card on the pricing page and a column in the comparison table. Prices are whole rupiah.', 'plan'],
            ['comparison-rows', 'Comparison table', 'One row per feature. Rows that are the same for every plan are listed under “included in every plan” instead.', 'row'],
            ['addons', 'Add-ons', 'Extras any plan can add, priced per unit.', 'add-on'],
        ] as [$collection, $heading, $description, $singular])
            <section data-pricing-collection="{{ $collection }}" data-singular="{{ $singular }}" class="rounded-2xl border border-cms-navy/8 bg-white/70 shadow-sm">
                <div class="flex flex-col justify-between gap-3 border-b border-cms-navy/8 p-5 sm:flex-row sm:items-center">
                    <div>
                        <h3 class="font-bold text-cms-navy">{{ $heading }}</h3>
                        <p class="mt-1 text-xs text-cms-navy/45">{{ $description }}</p>
                    </div>
                    <button data-pricing-new type="button" class="hidden h-10 shrink-0 rounded-xl bg-cms-gold px-4 text-xs font-bold text-cms-navy hover:bg-cms-gold/85">+ New {{ $singular }}</button>
                </div>
                <form data-pricing-form class="hidden grid gap-4 border-b border-cms-navy/8 bg-cms-canvas/40 p-5 sm:grid-cols-2"></form>
                <div data-pricing-list class="grid gap-2 p-4"><p class="p-2 text-sm text-cms-navy/45">Loading…</p></div>
            </section>
        @endforeach
    </section>
</x-cms.app-layout>
