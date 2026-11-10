@inject('richText', 'App\Features\ContentBlocks\Services\RichTextContent')

{{-- Rich text is HTML from the CMS editor, passed through RichTextContent's allowlist again on output (render()). --}}
@php
    $proseClass = implode(' ', [
        'text-[0.9375rem] leading-[1.9] text-public-ink [&>*:first-child]:mt-0',
        '[&_p]:mt-4 [&_strong]:font-normal [&_strong]:text-public-deep [&_u]:underline-offset-4 [&_mark]:rounded [&_mark]:bg-public-sage [&_mark]:px-1 [&_mark]:text-public-ink',
        '[&_a]:text-public-accent [&_a]:underline [&_a]:underline-offset-4',
        '[&_h2]:mt-10 [&_h2]:mb-3 [&_h2]:text-[clamp(1.25rem,2.4vw,1.5rem)] [&_h2]:leading-[1.35] [&_h2]:font-normal [&_h3]:mt-8 [&_h3]:mb-2 [&_h3]:text-lg [&_h3]:font-normal [&_h4]:mt-6 [&_h4]:mb-2 [&_h4]:text-base [&_h4]:font-normal',
        '[&_ol]:mt-4 [&_ol]:list-decimal [&_ol]:pl-6 [&_ul]:mt-4 [&_ul]:list-disc [&_ul]:pl-6 [&_li]:mt-2 [&_li_p]:mt-0',
        '[&_blockquote]:mt-6 [&_blockquote]:border-l-2 [&_blockquote]:border-public-accent [&_blockquote]:pl-5 [&_blockquote]:text-public-muted',
        '[&_[data-align=center]]:text-center [&_[data-align=right]]:text-right [&_[data-align=justify]]:text-justify',
        '[&_hr]:my-10 [&_hr]:border-public-line',
        '[&_pre]:mt-6 [&_pre]:overflow-x-auto [&_pre]:rounded-xl [&_pre]:bg-public-ink [&_pre]:p-5 [&_pre]:font-mono [&_pre]:text-[0.8125rem] [&_pre]:leading-[1.7] [&_pre]:text-white [&_:not(pre)>code]:rounded [&_:not(pre)>code]:bg-public-mint [&_:not(pre)>code]:px-1.5 [&_:not(pre)>code]:font-mono [&_:not(pre)>code]:text-[0.85em]',
        '[&_img]:mt-6 [&_img]:h-auto [&_img]:max-w-full [&_img]:rounded-2xl',
        '[&_[data-youtube-video]]:mt-6 [&_iframe]:aspect-video [&_iframe]:h-auto [&_iframe]:w-full [&_iframe]:rounded-2xl',
        // Players other than YouTube keep their provider's natural shape.
        '[&_[data-embed]]:mt-6 [&_[data-embed=maps]_iframe]:aspect-[4/3] [&_[data-embed=spotify]_iframe]:aspect-auto [&_[data-embed=spotify]_iframe]:h-88 [&_[data-embed=instagram]_iframe]:mx-auto [&_[data-embed=instagram]_iframe]:aspect-auto [&_[data-embed=instagram]_iframe]:h-170 [&_[data-embed=instagram]_iframe]:max-w-135 [&_[data-embed=tiktok]_iframe]:mx-auto [&_[data-embed=tiktok]_iframe]:aspect-auto [&_[data-embed=tiktok]_iframe]:h-185 [&_[data-embed=tiktok]_iframe]:max-w-81',
        // Wide tables scroll sideways on phones (RichTextContent::render() adds the wrapper) instead of stretching the page.
        '[&_[data-table-scroll]]:mt-6 [&_[data-table-scroll]]:overflow-x-auto [&_table]:w-full [&_table]:border-collapse [&_table]:text-sm',
        '[&_td]:border [&_td]:border-public-line [&_td]:px-4 [&_td]:py-2.5 [&_td]:align-top [&_th]:border [&_th]:border-public-line [&_th]:bg-public-mist [&_th]:px-4 [&_th]:py-2.5 [&_th]:text-left [&_th]:align-top [&_th]:font-normal [&_td_p]:mt-0 [&_th_p]:mt-0',
    ]);
@endphp

<x-public.container as="section" width="text" class="py-6">
    <div class="{{ $proseClass }}">
        {!! $richText->render($data['content'] ?? '') !!}
    </div>
</x-public.container>
