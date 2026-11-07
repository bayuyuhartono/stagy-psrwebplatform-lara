@props(['title' => 'Dashboard', 'section' => 'dashboard'])

<!DOCTYPE html>
{{-- The CMS interface is written in English regardless of the content locale. --}}
<html lang="en">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">
        <title>{{ $title }} · {{ config('app.name') }} CMS</title>
        <script type="application/json" data-cms-locales>@json(['default' => app(\App\Features\Localization\Services\LocaleRegistry::class)->default(), 'options' => app(\App\Features\Localization\Services\LocaleRegistry::class)->options()])</script>
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="min-h-screen overflow-x-hidden" data-cms-shell>
        <div class="min-h-screen lg:grid lg:grid-cols-[17rem_1fr]">
            <div data-sidebar-overlay class="fixed inset-0 z-30 hidden bg-cms-navy/55 backdrop-blur-sm lg:hidden"></div>

            <aside data-sidebar class="fixed inset-y-0 left-0 z-40 flex w-68 -translate-x-full flex-col bg-cms-navy text-white shadow-2xl transition-transform duration-200 lg:sticky lg:top-0 lg:h-screen lg:translate-x-0 lg:shadow-none">
                <div class="flex h-20 items-center gap-3 border-b border-white/10 px-6">
                    <span class="grid size-10 place-items-center rounded-xl bg-cms-gold font-bold text-cms-navy">P</span>
                    <div>
                        <p class="text-sm font-bold tracking-wide">Pesoros CMS</p>
                        <p class="text-xs text-white/55">Content workspace</p>
                    </div>
                </div>

                <nav class="flex-1 overflow-y-auto px-4 py-6" aria-label="CMS navigation">
                    <p class="px-3 text-[0.65rem] font-semibold tracking-[0.18em] text-white/40 uppercase">Workspace</p>
                    <div class="mt-2 grid gap-1">
                        @foreach ([
                            ['Dashboard', 'dashboard', route('cms.dashboard'), 'M3 13h8V3H3v10Zm10 8h8V11h-8v10ZM3 21h8v-6H3v6Zm10-12h8V3h-8v6Z'],
                            ['Pages', 'pages', route('cms.pages.index'), 'M6 2h9l5 5v15H6V2Zm8 1.5V8h4.5'],
                            ['Posts', 'posts', route('cms.posts.index'), 'M4 4h16v16H4V4Zm4 5h8M8 13h8M8 17h5'],
                            ['Services', 'services', route('cms.services.index'), 'M12 3 4 7v10l8 4 8-4V7l-8-4Zm0 0v18M4 7l8 4 8-4'],
                            ['Portfolio', 'portfolio', route('cms.portfolio.index'), 'M4 7h16v13H4V7Zm4 0V4h8v3M4 12h16'],
                            ['Pricing', 'pricing', route('cms.pricing.index'), 'M3 12V4h8l10 10-8 8L3 12Zm4.5-4.5h.01'],
                            ['Media', 'media', route('cms.media.index'), 'M4 5h16v14H4V5Zm3 10 3-3 2 2 3-4 3 5M8 9h.01'],
                            ['SEO', 'seo', route('cms.seo.index'), 'M4 12a8 8 0 1 1 16 0M12 8v4l3 2'],
                        ] as [$label, $navigationSection, $href, $icon])
                            <a href="{{ $href }}" data-nav-section="{{ $navigationSection }}" class="group flex items-center gap-3 rounded-xl px-3 py-2.5 text-sm font-medium hover:bg-white/8 hover:text-white {{ $section === $navigationSection ? 'bg-white/10 text-white' : 'text-white/65' }}">
                                <svg class="size-5 text-white/45 group-hover:text-cms-gold" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.7" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="{{ $icon }}" /></svg>
                                {{ $label }}
                            </a>
                        @endforeach
                    </div>

                    <p class="mt-7 px-3 text-[0.65rem] font-semibold tracking-[0.18em] text-white/40 uppercase">Manage</p>
                    <div class="mt-2 grid gap-1">
                        @foreach ([['Menus', 'menus', route('cms.menus.index')], ['Categories & Tags', 'taxonomies', route('cms.taxonomies.index')], ['Messages', 'messages', route('cms.messages.index')], ['Users & Roles', 'users-roles', route('cms.users-roles.index')], ['Audit Logs', 'audit-logs', route('cms.audit-logs.index')], ['Settings', 'settings', route('cms.settings.index')]] as [$label, $navigationSection, $href])
                            <a href="{{ $href }}" class="rounded-xl px-3 py-2.5 text-sm font-medium hover:bg-white/8 hover:text-white {{ $section === $navigationSection ? 'bg-white/10 text-white' : 'text-white/65' }}">{{ $label }}</a>
                        @endforeach
                    </div>
                </nav>

                <div class="border-t border-white/10 p-4">
                    <div class="flex items-center gap-3 rounded-xl bg-white/6 p-3">
                        <span class="grid size-9 shrink-0 place-items-center rounded-full bg-cms-green text-xs font-bold">{{ str(auth()->user()->name)->substr(0, 2)->upper() }}</span>
                        <div class="min-w-0 flex-1">
                            <p class="truncate text-sm font-semibold">{{ auth()->user()->name }}</p>
                            <p class="truncate text-xs text-white/45">{{ auth()->user()->email }}</p>
                        </div>
                        <form method="POST" action="{{ route('cms.logout') }}">
                            @csrf
                            <button type="submit" class="rounded-lg p-2 text-white/45 hover:bg-white/10 hover:text-white" aria-label="Sign out">
                                <svg class="size-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 9V5.25A2.25 2.25 0 0 0 13.5 3h-6A2.25 2.25 0 0 0 5.25 5.25v13.5A2.25 2.25 0 0 0 7.5 21h6a2.25 2.25 0 0 0 2.25-2.25V15M18 15l3-3m0 0-3-3m3 3H9" /></svg>
                            </button>
                        </form>
                    </div>
                </div>
            </aside>

            <div class="min-w-0">
                <header class="sticky top-0 z-20 flex h-20 items-center gap-4 border-b border-cms-navy/8 bg-cms-canvas/90 px-4 backdrop-blur-xl sm:px-6 lg:px-8">
                    <button data-sidebar-toggle type="button" class="grid size-10 place-items-center rounded-xl border border-cms-navy/10 bg-white/65 text-cms-navy lg:hidden" aria-label="Open navigation">
                        <svg class="size-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" d="M4 7h16M4 12h16M4 17h16" /></svg>
                    </button>
                    <div class="min-w-0 flex-1">
                        <p class="text-xs font-semibold tracking-[0.14em] text-cms-green uppercase">Content management</p>
                        <h1 class="truncate text-xl font-bold tracking-tight text-cms-navy">{{ $title }}</h1>
                    </div>
                    <div class="hidden w-full max-w-sm sm:block">
                        <label class="relative block">
                            <span class="sr-only">Search CMS</span>
                            <svg class="pointer-events-none absolute top-1/2 left-3.5 size-4 -translate-y-1/2 text-cms-navy/40" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-width="2" d="m21 21-4.35-4.35m2.1-5.4a7.5 7.5 0 1 1-15 0 7.5 7.5 0 0 1 15 0Z" /></svg>
                            <input data-global-search type="search" placeholder="Search content…" class="h-11 w-full rounded-xl border border-cms-navy/10 bg-white/70 pr-4 pl-10 text-sm outline-none placeholder:text-cms-navy/35 focus:border-cms-gold focus:ring-3 focus:ring-cms-gold/15">
                        </label>
                    </div>
                </header>

                <main class="p-4 sm:p-6 lg:p-8">
                    {{ $slot }}
                </main>
            </div>
        </div>

        @can('viewAny', App\Models\Media::class)
            <div data-media-picker class="fixed inset-0 z-60 hidden items-center justify-center bg-cms-navy/60 p-4 backdrop-blur-sm" role="dialog" aria-modal="true" aria-label="Choose media">
                <div class="flex max-h-[min(48rem,calc(100vh-2rem))] w-full max-w-5xl flex-col overflow-hidden rounded-3xl bg-cms-canvas shadow-2xl">
                    <header class="flex items-center gap-4 border-b border-cms-navy/10 bg-white/65 px-5 py-4 sm:px-6">
                        <div class="min-w-0 flex-1">
                            <h2 class="font-bold text-cms-navy">Choose media</h2>
                            <p class="mt-0.5 text-xs text-cms-navy/45">Select an existing file for this content block.</p>
                        </div>
                        <button data-media-picker-close type="button" class="grid size-9 place-items-center rounded-xl border border-cms-navy/10 bg-white text-cms-navy" aria-label="Close media picker">×</button>
                    </header>
                    <div class="border-b border-cms-navy/8 p-4 sm:px-6">
                        <input data-media-picker-search type="search" placeholder="Search media…" class="h-11 w-full rounded-xl border border-cms-navy/10 bg-white px-4 text-sm outline-none focus:border-cms-gold focus:ring-3 focus:ring-cms-gold/15">
                    </div>
                    <div data-media-picker-grid class="grid flex-1 grid-cols-2 gap-3 overflow-y-auto p-4 sm:grid-cols-3 sm:p-6 lg:grid-cols-4"></div>
                    <footer class="flex items-center justify-between gap-4 border-t border-cms-navy/8 bg-white/50 px-5 py-4 sm:px-6">
                        <a href="{{ route('cms.media.index') }}" class="text-xs font-bold text-cms-green">Open Media Library</a>
                        <button data-media-picker-close type="button" class="h-10 rounded-xl border border-cms-navy/10 bg-white px-4 text-sm font-bold text-cms-navy">Cancel</button>
                    </footer>
                </div>
            </div>
        @endcan
    </body>
</html>
