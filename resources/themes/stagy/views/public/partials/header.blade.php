@inject('contentUrls', 'App\Features\Seo\Services\PublicUrlGenerator')

@php
    $currentPath = '/'.ltrim(request()->path(), '/');
    $navItems = collect(($menus['header'] ?? $menu)?->rootItems ?? [])->map(function ($item) use ($contentUrls, $currentPath) {
        $href = $contentUrls->forMenuItem($item);
        $path = '/'.ltrim((string) parse_url($href, PHP_URL_PATH), '/');

        return ['label' => $item->label, 'href' => $href, 'target' => $item->target->value, 'active' => $path === $currentPath];
    });
    $demoUrl = localized_route('website.pages.show', $theme['demo_page_slug'] ?? 'demo');
@endphp

<header class="sticky top-0 z-40 border-b border-public-line bg-public-paper/95 backdrop-blur supports-[backdrop-filter]:bg-public-paper/90">
    <nav aria-label="{{ __('theme::site.main_navigation') }}" class="mx-auto flex max-w-290 items-center gap-3 px-4 py-2.5 sm:px-6 lg:gap-9 lg:py-3">
        <a href="{{ localized_route('home') }}" aria-label="{{ __('theme::site.go_home', ['name' => $siteName]) }}" class="mr-auto flex shrink-0 items-center lg:mr-0">
            <img src="{{ $logoUrl }}" alt="{{ $siteName }}" @if (blank($brandLogoUrl ?? null)) width="900" height="392" @endif class="h-9 w-auto lg:h-10">
        </a>

        <div class="hidden flex-1 justify-center gap-7 lg:flex">
            @foreach ($navItems as $item)
                <a href="{{ $item['href'] }}" target="{{ $item['target'] }}" @if ($item['active']) aria-current="page" @endif @class([
                    'whitespace-nowrap border-b-2 pt-3 pb-2.5 text-[0.8125rem] tracking-[0.04em] transition',
                    'border-public-accent text-public-ink' => $item['active'],
                    'border-transparent text-public-muted hover:text-public-ink' => ! $item['active'],
                ])>{{ $item['label'] }}</a>
            @endforeach
        </div>

        <div class="hidden items-center gap-5 lg:flex">
            @include('public.partials.language-switcher')
            <a href="{{ $demoUrl }}" class="inline-flex min-h-11 items-center whitespace-nowrap rounded-[0.625rem] bg-public-ink px-4.5 text-[0.8125rem] tracking-[0.04em] text-white transition hover:bg-public-deep">{{ __('theme::site.request_demo') }}</a>
        </div>

        <a href="{{ $demoUrl }}" class="inline-flex min-h-11 items-center whitespace-nowrap rounded-[0.625rem] bg-public-ink px-3.5 text-xs tracking-[0.04em] text-white lg:hidden">{{ __('theme::site.request_demo') }}</a>
        <button data-public-menu-toggle type="button" aria-expanded="false" aria-controls="stagy-mobile-menu" aria-label="{{ __('theme::site.open_menu') }}" class="group grid size-11 place-items-center rounded-[0.625rem] border border-public-line bg-public-paper text-public-ink lg:hidden">
            <x-public.icon name="menu" class="size-5 group-aria-expanded:hidden" stroke-width="2.2" />
            <x-public.icon name="close" class="hidden size-5 group-aria-expanded:block" stroke-width="2.2" />
        </button>
    </nav>

    <div id="stagy-mobile-menu" data-public-mobile-menu class="hidden border-t border-public-line px-4 pt-2 pb-5 lg:hidden">
        <div class="flex flex-col">
            @foreach ($navItems as $item)
                <a href="{{ $item['href'] }}" target="{{ $item['target'] }}" @if ($item['active']) aria-current="page" @endif @class([
                    'flex min-h-13 items-center justify-between border-b border-public-mint px-1 py-4 text-sm tracking-[0.04em]',
                    'text-public-ink' => $item['active'],
                    'text-public-muted' => ! $item['active'],
                ])>
                    <span>{{ $item['label'] }}</span>
                    <span aria-hidden="true" @class(['size-2 rounded-full', 'bg-public-accent' => $item['active']])></span>
                </a>
            @endforeach
        </div>
        <div class="flex items-center justify-between px-1 pt-4 text-xs tracking-[0.04em] text-public-muted">
            <span>{{ __('theme::site.language') }}</span>
            @include('public.partials.language-switcher')
        </div>
    </div>
</header>
