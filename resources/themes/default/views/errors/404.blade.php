@inject('websiteSettings', 'App\Features\WebsiteSettings\Repositories\WebsiteSettingRepository')
@inject('menus', 'App\Features\Menus\Repositories\MenuRepository')

@php
    $settings = $websiteSettings->public()->mapWithKeys(fn ($setting) => [$setting->group->value => $setting->settings]);
    $menu = $menus->findActiveByLocationOrNull('header');
@endphp

<x-public.layout :settings="$settings" :menu="$menu" metaTitle="404 — Page Not Found" metaDescription="The page you are looking for could not be found.">
    <section class="relative flex min-h-[70vh] items-center justify-center overflow-hidden bg-public-ink text-white lg:min-h-screen">
        <div class="absolute inset-0 bg-[radial-gradient(ellipse_at_center,var(--color-public-surface),transparent_70%)] opacity-30"></div>
        <div class="relative z-10 mx-auto max-w-3xl px-5 text-center sm:px-8 lg:px-10">
            <p class="text-xs font-bold uppercase tracking-[0.24em] text-public-accent">Error 404</p>
            <h1 class="mt-6 text-7xl font-semibold leading-[0.9] tracking-[-0.05em] sm:text-8xl lg:text-9xl">Page not<br>found.</h1>
            <p class="mt-8 text-lg text-white/50">The page you are looking for doesn't exist or has been moved.</p>
            <div class="mt-12">
                <a href="{{ route('home') }}" class="inline-flex items-center gap-4 rounded-full bg-white px-12 py-4 text-sm font-bold text-public-ink transition hover:bg-white/90">Return to home <span>↗</span></a>
            </div>
        </div>
    </section>
</x-public.layout>
