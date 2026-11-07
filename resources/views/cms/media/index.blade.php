<x-cms.app-layout title="Media Library" section="media">
    <section
        data-media-library
        data-can-upload="{{ auth()->user()->hasPermission('media.upload') ? 'true' : 'false' }}"
        data-can-update="{{ auth()->user()->hasPermission('media.update') ? 'true' : 'false' }}"
        data-can-delete="{{ auth()->user()->hasPermission('media.delete') ? 'true' : 'false' }}"
        data-can-restore="{{ auth()->user()->hasPermission('media.restore') ? 'true' : 'false' }}"
        class="grid gap-6"
    >
        <div class="flex flex-col justify-between gap-4 sm:flex-row sm:items-end">
            <div>
                <p class="text-sm text-cms-navy/55">Upload, organize, and reuse files across your website.</p>
                <h2 class="mt-1 text-2xl font-bold tracking-tight text-cms-navy">Media Library</h2>
            </div>
            @can('create', App\Models\Media::class)
                <button data-open-upload type="button" class="inline-flex h-11 items-center justify-center gap-2 rounded-xl bg-cms-gold px-5 text-sm font-bold text-cms-navy hover:bg-cms-gold/85">
                    <span class="text-lg leading-none">+</span> Upload media
                </button>
            @endcan
        </div>

        <div class="grid gap-3 rounded-2xl border border-cms-navy/8 bg-white/70 p-4 shadow-sm sm:grid-cols-[1fr_12rem_auto] sm:p-5">
            <label class="relative block">
                <span class="sr-only">Search media</span>
                <svg class="pointer-events-none absolute top-1/2 left-3.5 size-4 -translate-y-1/2 text-cms-navy/35" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-width="2" d="m21 21-4.35-4.35m2.1-5.4a7.5 7.5 0 1 1-15 0 7.5 7.5 0 0 1 15 0Z" /></svg>
                <input data-media-search type="search" placeholder="Search filenames…" class="h-11 w-full rounded-xl border border-cms-navy/10 bg-white px-4 pl-10 text-sm outline-none focus:border-cms-gold focus:ring-3 focus:ring-cms-gold/15">
            </label>
            <input data-media-folder type="text" placeholder="Filter folder" class="h-11 rounded-xl border border-cms-navy/10 bg-white px-3 text-sm outline-none focus:border-cms-gold focus:ring-3 focus:ring-cms-gold/15">
            @if (auth()->user()->hasPermission('media.restore'))
                <label class="flex h-11 items-center gap-2 rounded-xl border border-cms-navy/10 bg-white px-3 text-sm font-semibold text-cms-navy/65">
                    <input data-media-trash type="checkbox" class="size-4 rounded border-cms-navy/20 text-cms-green focus:ring-cms-green/20"> Trash
                </label>
            @endif
        </div>

        <div data-media-error class="hidden rounded-xl border border-red-200 bg-red-50 p-4 text-sm text-red-800"></div>
        <div data-media-grid class="grid grid-cols-2 gap-4 sm:grid-cols-3 xl:grid-cols-4 2xl:grid-cols-5">
            @for ($index = 0; $index < 10; $index++)
                <div class="aspect-square animate-pulse rounded-2xl bg-cms-navy/6"></div>
            @endfor
        </div>
        <div class="flex flex-col items-center justify-between gap-3 sm:flex-row">
            <p data-media-summary class="text-xs text-cms-navy/45">Loading media…</p>
            <div class="flex gap-2">
                <button data-media-previous type="button" class="h-9 rounded-lg border border-cms-navy/10 bg-white px-3 text-xs font-semibold text-cms-navy disabled:opacity-35">Previous</button>
                <button data-media-next type="button" class="h-9 rounded-lg border border-cms-navy/10 bg-white px-3 text-xs font-semibold text-cms-navy disabled:opacity-35">Next</button>
            </div>
        </div>

        @can('create', App\Models\Media::class)
            <div data-upload-modal class="fixed inset-0 z-60 hidden items-center justify-center bg-cms-navy/60 p-4 backdrop-blur-sm" role="dialog" aria-modal="true" aria-label="Upload media">
                <form data-upload-form class="w-full max-w-lg rounded-3xl bg-cms-canvas p-5 shadow-2xl sm:p-6">
                    <div class="flex items-center justify-between gap-4">
                        <div><h3 class="font-bold text-cms-navy">Upload media</h3><p class="mt-1 text-xs text-cms-navy/45">Images, video, or PDF up to 50 MB.</p></div>
                        <button data-close-upload type="button" class="grid size-9 place-items-center rounded-xl border border-cms-navy/10 bg-white">×</button>
                    </div>
                    <label class="mt-6 grid cursor-pointer place-items-center rounded-2xl border-2 border-dashed border-cms-navy/15 bg-white/55 px-5 py-10 text-center hover:border-cms-gold">
                        <svg class="size-8 text-cms-green" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.7" d="M12 16V4m0 0L8 8m4-4 4 4M4 15v4h16v-4" /></svg>
                        <span data-upload-filename class="mt-3 text-sm font-semibold text-cms-navy">Choose a file</span>
                        <input data-upload-file name="file" type="file" accept="image/jpeg,image/png,image/webp,image/gif,application/pdf,video/mp4,video/webm" required class="sr-only">
                    </label>
                    <div class="mt-5 grid gap-4 sm:grid-cols-2">
                        <label class="grid gap-1.5"><span class="text-xs font-semibold text-cms-navy/60">Folder</span><input name="folder" placeholder="pages/home" class="h-10 rounded-xl border border-cms-navy/10 bg-white px-3 text-sm outline-none focus:border-cms-gold"></label>
                        <label class="grid gap-1.5"><span class="text-xs font-semibold text-cms-navy/60">Alt text</span><input name="alt_text" class="h-10 rounded-xl border border-cms-navy/10 bg-white px-3 text-sm outline-none focus:border-cms-gold"></label>
                        <label class="grid gap-1.5 sm:col-span-2"><span class="text-xs font-semibold text-cms-navy/60">Caption</span><textarea name="caption" class="min-h-20 rounded-xl border border-cms-navy/10 bg-white px-3 py-2 text-sm outline-none focus:border-cms-gold"></textarea></label>
                    </div>
                    <div data-upload-error class="mt-4 hidden text-sm text-red-700"></div>
                    <div class="mt-6 flex justify-end gap-2"><button data-close-upload type="button" class="h-10 rounded-xl border border-cms-navy/10 bg-white px-4 text-sm font-bold">Cancel</button><button data-upload-submit type="submit" class="h-10 rounded-xl bg-cms-green px-5 text-sm font-bold text-white">Upload</button></div>
                </form>
            </div>
        @endcan

        <div data-media-detail class="fixed inset-0 z-60 hidden items-center justify-center bg-cms-navy/60 p-4 backdrop-blur-sm" role="dialog" aria-modal="true" aria-label="Media details">
            <div class="grid max-h-[calc(100vh-2rem)] w-full max-w-3xl overflow-y-auto rounded-3xl bg-cms-canvas shadow-2xl md:grid-cols-[minmax(0,1fr)_20rem]">
                <div data-detail-preview class="grid min-h-64 place-items-center bg-cms-navy p-6"></div>
                <div class="grid content-start gap-5 p-5 sm:p-6">
                    <div class="flex items-start justify-between gap-3"><div class="min-w-0"><h3 data-detail-name class="truncate font-bold text-cms-navy"></h3><p data-detail-meta class="mt-1 text-xs text-cms-navy/45"></p></div><button data-close-detail type="button" class="grid size-9 shrink-0 place-items-center rounded-xl border border-cms-navy/10 bg-white">×</button></div>
                    <form data-detail-form class="grid gap-4">
                        <label class="grid gap-1.5"><span class="text-xs font-semibold text-cms-navy/60">Folder</span><input data-detail-folder class="h-10 rounded-xl border border-cms-navy/10 bg-white px-3 text-sm outline-none focus:border-cms-gold"></label>
                        <label class="grid gap-1.5"><span class="text-xs font-semibold text-cms-navy/60">Alt text</span><input data-detail-alt class="h-10 rounded-xl border border-cms-navy/10 bg-white px-3 text-sm outline-none focus:border-cms-gold"></label>
                        <label class="grid gap-1.5"><span class="text-xs font-semibold text-cms-navy/60">Caption</span><textarea data-detail-caption class="min-h-24 rounded-xl border border-cms-navy/10 bg-white px-3 py-2 text-sm outline-none focus:border-cms-gold"></textarea></label>
                        <div data-detail-error class="hidden text-sm text-red-700"></div>
                        <button data-detail-save type="submit" class="h-10 rounded-xl bg-cms-green px-4 text-sm font-bold text-white">Save changes</button>
                    </form>
                    <div class="border-t border-cms-navy/10 pt-4">
                        <button data-detail-delete type="button" class="text-sm font-bold text-red-700">Move to trash</button>
                        <button data-detail-restore type="button" class="hidden text-sm font-bold text-cms-green">Restore media</button>
                    </div>
                </div>
            </div>
        </div>
    </section>
</x-cms.app-layout>
