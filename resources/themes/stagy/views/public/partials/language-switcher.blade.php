{{-- "ID · EN": each entry links to this page's translation, or to that language's home page when none exists. --}}
<div class="flex items-center gap-1.5 text-xs tracking-[0.04em]">
    @foreach ($languageSwitcher ?? [] as $language)
        @if (! $loop->first)
            <span aria-hidden="true" class="text-public-line">·</span>
        @endif
        <a href="{{ $language['url'] }}" hreflang="{{ $language['locale'] }}" lang="{{ $language['locale'] }}"
            @if ($language['active']) aria-current="true" @else aria-label="{{ __('theme::site.switch_language', ['language' => $language['name']]) }}" @endif
            @class([
                'inline-flex min-h-11 items-center px-1',
                'text-public-ink underline decoration-public-accent decoration-2 underline-offset-6' => $language['active'],
                'text-public-muted hover:text-public-ink' => ! $language['active'],
            ])>{{ $language['label'] }}</a>
    @endforeach
</div>
