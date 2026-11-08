<x-cms.app-layout title="Menus" section="menus">
    <section data-menu-manager data-menu-locations='@json((object) $menuLocations)' data-can-create="{{ auth()->user()->hasPermission('menus.create') ? 'true' : 'false' }}" data-can-update="{{ auth()->user()->hasPermission('menus.update') ? 'true' : 'false' }}" data-can-delete="{{ auth()->user()->hasPermission('menus.delete') ? 'true' : 'false' }}" class="grid gap-6">
        <div class="flex flex-col justify-between gap-4 sm:flex-row sm:items-end">
            <div>
                <p class="text-sm text-cms-navy/55">Build header, footer, and utility navigation.</p>
                <h2 class="mt-1 text-2xl font-bold">Navigation menus</h2>
            </div>
            @if (auth()->user()->hasPermission('menus.create'))
                <button data-menu-new type="button" class="h-11 rounded-xl bg-cms-gold px-5 text-sm font-bold">+ New menu</button>
            @endif
        </div>

        <div data-menu-notice class="hidden rounded-xl border p-4 text-sm"></div>

        <div class="grid items-start gap-5 xl:grid-cols-[17rem_minmax(0,1fr)]">
            <aside class="overflow-hidden rounded-2xl border border-cms-navy/8 bg-white/70 shadow-sm">
                <div class="border-b border-cms-navy/8 p-4">
                    <h3 class="font-bold">Menus</h3>
                    <p class="mt-1 text-xs text-cms-navy/45">Choose a navigation location.</p>
                </div>
                <div data-menu-list class="grid gap-1 p-2"><p class="p-3 text-sm text-cms-navy/45">Loading menus…</p></div>
            </aside>

            <div data-menu-workspace class="hidden min-w-0 grid gap-5">
                <form data-menu-form class="grid min-w-0 gap-5 rounded-2xl border border-cms-navy/8 bg-white/70 p-5 shadow-sm">
                    <input data-menu-id type="hidden">
                    <div class="grid gap-4 md:grid-cols-3">
                        <label class="grid min-w-0 gap-2">
                            <span class="text-xs font-semibold">Menu name</span>
                            <input data-menu-name required maxlength="255" class="h-11 w-full min-w-0 rounded-xl border border-cms-navy/10 bg-white px-4 text-sm">
                        </label>
                        <label class="grid min-w-0 gap-2">
                            <span class="text-xs font-semibold">Location</span>
                            <select data-menu-location required class="h-11 w-full min-w-0 rounded-xl border border-cms-navy/10 bg-white px-3 text-sm disabled:bg-cms-canvas/65">
                                <option value="" disabled selected>Choose a location</option>
                                @foreach ($menuLocations as $location => $label)
                                    <option value="{{ $location }}">{{ $label }}</option>
                                @endforeach
                            </select>
                        </label>
                        <label class="grid min-w-0 gap-2">
                            <span class="text-xs font-semibold">Language</span>
                            <select data-menu-locale class="h-11 w-full min-w-0 rounded-xl border border-cms-navy/10 bg-white px-3 text-sm disabled:bg-cms-canvas/65">
                                @foreach (app(\App\Features\Localization\Services\LocaleRegistry::class)->options() as $code => $locale)
                                    <option value="{{ $code }}">{{ $locale['name'] }}</option>
                                @endforeach
                            </select>
                        </label>
                    </div>
                    <div class="flex flex-wrap items-center justify-between gap-3 border-t border-cms-navy/8 pt-4">
                        <label class="flex items-center gap-2 text-sm font-semibold">
                            <input data-menu-active type="checkbox" class="size-4 accent-cms-green"> Active
                        </label>
                        <div class="flex items-center gap-2">
                            <button data-menu-delete type="button" class="hidden h-11 rounded-xl border border-red-200 bg-white px-4 text-sm font-bold text-red-700">Delete menu</button>
                            <button data-menu-save class="h-11 rounded-xl bg-cms-green px-5 text-sm font-bold text-white">Save menu</button>
                        </div>
                    </div>
                </form>

                <section data-menu-items-section class="hidden overflow-hidden rounded-2xl border border-cms-navy/8 bg-white/70 shadow-sm">
                    <div class="flex flex-col justify-between gap-3 border-b border-cms-navy/8 p-5 sm:flex-row sm:items-center">
                        <div>
                            <h3 class="font-bold">Menu items</h3>
                            <p class="mt-1 text-xs text-cms-navy/45">Use parent items to create dropdown navigation.</p>
                        </div>
                        @if (auth()->user()->hasPermission('menus.update'))
                            <button data-item-new type="button" class="h-10 rounded-xl bg-cms-gold px-4 text-xs font-bold">+ Add item</button>
                        @endif
                    </div>
                    <div data-menu-items class="grid gap-2 p-5"></div>
                </section>

                <form data-item-form class="hidden grid gap-4 rounded-2xl border border-cms-green/15 bg-white p-5 shadow-sm sm:grid-cols-2">
                    <input data-item-id type="hidden">
                    <div class="sm:col-span-2"><h3 data-item-form-title class="font-bold">Add menu item</h3></div>
                    <label class="grid min-w-0 gap-2"><span class="text-xs font-semibold">Label</span><input data-item-label required maxlength="255" class="h-11 rounded-xl border border-cms-navy/10 px-4 text-sm"></label>
                    <label class="grid min-w-0 gap-2"><span class="text-xs font-semibold">Link type</span><select data-item-type class="h-11 rounded-xl border border-cms-navy/10 bg-white px-3 text-sm"><option value="custom">Custom URL</option><option value="page">Published page</option></select></label>
                    <label data-item-url-field class="grid min-w-0 gap-2"><span class="text-xs font-semibold">URL</span><input data-item-url placeholder="/contact or https://example.com" class="h-11 rounded-xl border border-cms-navy/10 px-4 text-sm"></label>
                    <label data-item-page-field class="hidden grid min-w-0 gap-2"><span class="text-xs font-semibold">Page</span><select data-item-page class="h-11 rounded-xl border border-cms-navy/10 bg-white px-3 text-sm"><option value="">Choose a page</option></select></label>
                    <label class="grid min-w-0 gap-2"><span class="text-xs font-semibold">Parent item</span><select data-item-parent class="h-11 rounded-xl border border-cms-navy/10 bg-white px-3 text-sm"><option value="">No parent</option></select></label>
                    <label class="grid min-w-0 gap-2"><span class="text-xs font-semibold">Open link in</span><select data-item-target class="h-11 rounded-xl border border-cms-navy/10 bg-white px-3 text-sm"><option value="_self">Same tab</option><option value="_blank">New tab</option></select></label>
                    <label class="grid min-w-0 gap-2"><span class="text-xs font-semibold">Display order</span><input data-item-position type="number" min="0" max="65535" value="0" class="h-11 rounded-xl border border-cms-navy/10 px-4 text-sm"></label>
                    <div class="flex items-center justify-end gap-2 border-t border-cms-navy/8 pt-4 sm:col-span-2">
                        <button data-item-cancel type="button" class="h-11 rounded-xl border border-cms-navy/10 bg-white px-4 text-sm font-bold">Cancel</button>
                        <button data-item-save class="h-11 rounded-xl bg-cms-green px-5 text-sm font-bold text-white">Save item</button>
                    </div>
                </form>
            </div>

            <div data-menu-empty class="rounded-2xl border border-dashed border-cms-navy/15 bg-white/40 p-12 text-center text-sm text-cms-navy/45 xl:col-start-2">Choose a menu to manage its navigation items.</div>
        </div>
    </section>
</x-cms.app-layout>
