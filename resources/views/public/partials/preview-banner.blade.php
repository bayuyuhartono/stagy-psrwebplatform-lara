{{-- Shown when a CMS user previews saved content that visitors may not see yet (cms.posts.preview). --}}
@if ($previewing ?? false)
    <div role="status" class="relative z-60 flex flex-wrap items-center justify-center gap-x-4 gap-y-1 bg-public-ink px-4 py-2.5 text-center text-sm text-white">
        <strong>Preview</strong>
        <span class="opacity-80">This is the last saved version. It is not public until it is published.</span>
        @isset($previewEditUrl)
            <a href="{{ $previewEditUrl }}" class="underline underline-offset-4">Back to the editor</a>
        @endisset
    </div>
@endif
