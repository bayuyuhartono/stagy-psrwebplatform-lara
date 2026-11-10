{{-- Language and translations sidebar for translatable editors (pages, posts). Driven by the page editor script. --}}
<section data-translation-panel class="grid min-w-0 gap-4 rounded-2xl border border-cms-navy/8 bg-white/70 p-5 shadow-sm">
    <h3 class="font-bold text-cms-navy">Language</h3>
    <label class="grid min-w-0 gap-2">
        <span class="text-xs font-semibold text-cms-navy/60">Content language</span>
        <select data-content-locale class="h-11 w-full min-w-0 rounded-xl border border-cms-navy/10 bg-white px-3 text-sm font-semibold text-cms-navy outline-none focus:border-cms-gold disabled:bg-cms-canvas/65 disabled:text-cms-navy/60">
            @foreach (app(\App\Features\Localization\Services\LocaleRegistry::class)->options() as $code => $locale)
                <option value="{{ $code }}">{{ $locale['name'] }}</option>
            @endforeach
        </select>
    </label>
    <p data-translation-source class="hidden rounded-xl bg-cms-canvas p-3 text-xs leading-5 text-cms-navy/60"></p>
    <div data-translation-list class="hidden grid gap-2">
        <span class="text-xs font-semibold text-cms-navy/60">Translations</span>
    </div>
    <p class="text-xs leading-5 text-cms-navy/45">The language is fixed once saved. Each translation is its own entry with its own URL slug.</p>
</section>
