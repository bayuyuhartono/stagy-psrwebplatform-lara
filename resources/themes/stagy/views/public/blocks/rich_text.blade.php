{{-- Rich text is written as Markdown (## headings, lists, **bold**, links). Raw HTML is stripped and unsafe links dropped. --}}
<x-public.container as="section" width="text" class="py-6">
    <div class="text-[0.9375rem] leading-[1.9] text-public-ink [&_a]:text-public-accent [&_a]:underline [&_a]:underline-offset-4 [&_blockquote]:border-l-2 [&_blockquote]:border-public-accent [&_blockquote]:pl-5 [&_blockquote]:text-public-muted [&_h2]:mt-10 [&_h2]:mb-3 [&_h2]:text-[clamp(1.25rem,2.4vw,1.5rem)] [&_h2]:leading-[1.35] [&_h2]:font-normal [&_h3]:mt-8 [&_h3]:mb-2 [&_h3]:text-lg [&_h3]:font-normal [&_li]:mt-2 [&_ol]:list-decimal [&_ol]:pl-6 [&_p]:mt-4 [&_strong]:font-normal [&_strong]:text-public-deep [&_ul]:list-disc [&_ul]:pl-6 [&>*:first-child]:mt-0">
        {!! Str::markdown((string) ($data['content'] ?? ''), ['html_input' => 'strip', 'allow_unsafe_links' => false]) !!}
    </div>
</x-public.container>
