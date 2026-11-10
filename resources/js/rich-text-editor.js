import { Editor, Node } from '@tiptap/core';
import Highlight from '@tiptap/extension-highlight';
import Image from '@tiptap/extension-image';
import { TableKit } from '@tiptap/extension-table';
import TextAlign from '@tiptap/extension-text-align';
import Youtube from '@tiptap/extension-youtube';
import StarterKit from '@tiptap/starter-kit';

/**
 * WYSIWYG editing for the rich_text block, stored as HTML. The original
 * textarea stays in the form (hidden) as the value the block serializer reads;
 * every edit writes the editor's HTML back to it. The server sanitizes that
 * HTML (App\Features\ContentBlocks\Services\RichTextContent) on save and on
 * output, so the editor only offers what that allowlist keeps.
 */

/**
 * Alignment is stored as data-align rather than an inline style: the server
 * strips every style attribute, and themes style [data-align] themselves.
 */
const Alignment = TextAlign.extend({
    addGlobalAttributes() {
        return [{
            types: this.options.types,
            attributes: {
                textAlign: {
                    default: null,
                    parseHTML: (element) => element.getAttribute('data-align') || element.style.textAlign || null,
                    renderHTML: (attributes) => (attributes.textAlign && attributes.textAlign !== 'left' ? { 'data-align': attributes.textAlign } : {}),
                },
            },
        }];
    },
});

const EMBED_LABELS = { youtube: 'YouTube', vimeo: 'Vimeo', maps: 'Google Maps', spotify: 'Spotify', instagram: 'Instagram', tiktok: 'TikTok' };

/**
 * Turn a share link or pasted <iframe> embed code into a provider's player URL.
 * Mirrors the server allowlist in RichTextAttributeSanitizer::EMBED_PROVIDERS.
 *
 * @returns {{provider: string, src: string}|null}
 */
const toEmbed = (input) => {
    let raw = input.trim();
    const iframeSource = raw.match(/<iframe[^>]+src=["']([^"']+)["']/i);
    if (iframeSource) raw = iframeSource[1].replaceAll('&amp;', '&');
    let url;
    try {
        url = new URL(/^https?:\/\//i.test(raw) ? raw : `https://${raw}`);
    } catch {
        return null;
    }
    const host = url.hostname.replace(/^(www|m)\./, '');
    const path = url.pathname;

    if (['youtube.com', 'youtu.be', 'youtube-nocookie.com'].includes(host)) return { provider: 'youtube', src: url.href };
    if (host === 'vimeo.com' || host === 'player.vimeo.com') {
        const id = path.match(/(\d+)/)?.[1];
        return id ? { provider: 'vimeo', src: `https://player.vimeo.com/video/${id}` } : null;
    }
    if ((host === 'google.com' || host === 'maps.google.com') && path === '/maps/embed') return { provider: 'maps', src: `https://www.google.com/maps/embed${url.search}` };
    if (host === 'open.spotify.com') {
        const match = path.replace(/^\/intl-[a-z-]+/i, '').replace(/^\/embed/, '').match(/^\/(track|album|playlist|episode|show|artist)\/(\w+)/);
        return match ? { provider: 'spotify', src: `https://open.spotify.com/embed/${match[1]}/${match[2]}` } : null;
    }
    if (host === 'instagram.com') {
        const match = path.match(/^\/(p|reel)\/([\w-]+)/);
        return match ? { provider: 'instagram', src: `https://www.instagram.com/${match[1]}/${match[2]}/embed` } : null;
    }
    if (host === 'tiktok.com') {
        const id = path.match(/\/video\/(\d+)/)?.[1] ?? path.match(/^\/embed\/v2\/(\d+)/)?.[1];
        return id ? { provider: 'tiktok', src: `https://www.tiktok.com/embed/v2/${id}` } : null;
    }

    return null;
};

/** A provider's player (everything but YouTube, which keeps Tiptap's own node), stored as div[data-embed] > iframe. */
const Embed = Node.create({
    name: 'embed',
    group: 'block',
    atom: true,
    draggable: true,
    addAttributes() {
        return { provider: { default: null, rendered: false }, src: { default: null, rendered: false } };
    },
    parseHTML() {
        return [{
            tag: 'div[data-embed]',
            getAttrs: (element) => {
                const src = element.querySelector('iframe')?.getAttribute('src');
                return src ? { provider: element.getAttribute('data-embed'), src } : false;
            },
        }];
    },
    renderHTML({ node }) {
        return ['div', { 'data-embed': node.attrs.provider }, ['iframe', { src: node.attrs.src, title: `${EMBED_LABELS[node.attrs.provider] ?? 'Embedded'} content`, allowfullscreen: 'true', loading: 'lazy' }]];
    },
});

/** Line breaks after block-level closing tags so the HTML source view is readable without changing the content. */
const formatSource = (html) => html.replace(/(<\/(?:p|h[1-6]|ul|ol|li|blockquote|table|thead|tbody|tr|pre|div)>|<hr>|<img[^>]*>)/g, '$1\n').trim();

const buttonClass = 'grid h-8 min-w-8 place-items-center rounded-lg px-2 text-xs font-bold text-cms-navy/70 hover:bg-cms-canvas hover:text-cms-navy disabled:pointer-events-none disabled:opacity-30 aria-pressed:bg-cms-navy aria-pressed:text-white';

const editorClass = [
    'min-h-72 max-w-none px-4 py-3 text-sm leading-7 text-cms-navy outline-none [&>*:first-child]:mt-0',
    '[&_p]:my-2 [&_a]:text-cms-green [&_a]:underline [&_mark]:rounded [&_mark]:bg-cms-gold/35 [&_mark]:px-0.5',
    '[&_h2]:mt-5 [&_h2]:mb-2 [&_h2]:text-xl [&_h2]:font-bold [&_h3]:mt-4 [&_h3]:mb-1.5 [&_h3]:text-base [&_h3]:font-bold [&_h4]:mt-3 [&_h4]:mb-1 [&_h4]:text-sm [&_h4]:font-bold',
    '[&_li]:mt-1 [&_li_p]:my-0 [&_ol]:list-decimal [&_ol]:pl-6 [&_ul]:list-disc [&_ul]:pl-6',
    '[&_blockquote]:my-3 [&_blockquote]:border-l-2 [&_blockquote]:border-cms-gold [&_blockquote]:pl-4 [&_blockquote]:text-cms-navy/60',
    '[&_[data-align=center]]:text-center [&_[data-align=right]]:text-right [&_[data-align=justify]]:text-justify',
    '[&_hr]:my-5 [&_hr]:border-cms-navy/15 [&_hr.ProseMirror-selectednode]:border-cms-gold',
    '[&_pre]:my-3 [&_pre]:overflow-x-auto [&_pre]:rounded-xl [&_pre]:bg-cms-navy [&_pre]:p-4 [&_pre]:font-mono [&_pre]:text-xs [&_pre]:text-white [&_:not(pre)>code]:rounded [&_:not(pre)>code]:bg-cms-canvas [&_:not(pre)>code]:px-1 [&_:not(pre)>code]:font-mono',
    '[&_img]:my-3 [&_img]:h-auto [&_img]:max-w-full [&_img]:rounded-xl [&_img.ProseMirror-selectednode]:outline-3 [&_img.ProseMirror-selectednode]:outline-cms-gold',
    '[&_iframe]:my-3 [&_iframe]:aspect-video [&_iframe]:h-auto [&_iframe]:w-full [&_iframe]:rounded-xl [&_.ProseMirror-selectednode_iframe]:outline-3 [&_.ProseMirror-selectednode_iframe]:outline-cms-gold',
    '[&_[data-embed]]:my-3 [&_[data-embed=maps]_iframe]:aspect-[4/3] [&_[data-embed=spotify]_iframe]:aspect-auto [&_[data-embed=spotify]_iframe]:h-88 [&_[data-embed=instagram]_iframe]:mx-auto [&_[data-embed=instagram]_iframe]:aspect-auto [&_[data-embed=instagram]_iframe]:h-170 [&_[data-embed=instagram]_iframe]:max-w-135 [&_[data-embed=tiktok]_iframe]:mx-auto [&_[data-embed=tiktok]_iframe]:aspect-auto [&_[data-embed=tiktok]_iframe]:h-185 [&_[data-embed=tiktok]_iframe]:max-w-81 [&_[data-embed].ProseMirror-selectednode_iframe]:outline-3 [&_[data-embed].ProseMirror-selectednode_iframe]:outline-cms-gold',
    '[&_.tableWrapper]:my-3 [&_.tableWrapper]:overflow-x-auto [&_table]:w-full [&_table]:border-collapse [&_table]:text-xs',
    '[&_td]:relative [&_td]:min-w-20 [&_td]:border [&_td]:border-cms-navy/15 [&_td]:px-2.5 [&_td]:py-1.5 [&_td]:align-top [&_th]:relative [&_th]:min-w-20 [&_th]:border [&_th]:border-cms-navy/15 [&_th]:bg-cms-canvas [&_th]:px-2.5 [&_th]:py-1.5 [&_th]:text-left [&_th]:align-top [&_td_p]:my-0 [&_th_p]:my-0',
    '[&_.selectedCell]:bg-cms-gold/20',
].join(' ');

const normalizeUrl = (url) => {
    const trimmed = url.trim();
    if (trimmed === '' || /^(https?:|mailto:|tel:|\/|#)/i.test(trimmed)) return trimmed;

    return `https://${trimmed}`;
};

/** Media on this site's public disk is stored as a path so content survives a domain change. */
const mediaSource = (url) => {
    try {
        const parsed = new URL(url, window.location.origin);

        return parsed.pathname.startsWith('/storage/') ? `${parsed.pathname}${parsed.search}` : parsed.href;
    } catch {
        return url;
    }
};

export function mountRichTextEditor(textarea) {
    const shell = document.createElement('div');
    shell.className = 'rounded-xl border border-cms-navy/10 bg-white focus-within:border-cms-gold';
    const toolbar = document.createElement('div');
    // Sticks under the 5rem CMS header so it stays in reach in long articles.
    toolbar.className = 'sticky top-20 z-10 grid gap-1 rounded-t-xl border-b border-cms-navy/8 bg-cms-canvas/95 p-1.5 backdrop-blur';
    toolbar.setAttribute('role', 'toolbar');
    toolbar.setAttribute('aria-label', 'Text formatting');
    const mainRow = document.createElement('div');
    mainRow.className = 'flex flex-wrap items-center gap-1';
    const tableRow = document.createElement('div');
    tableRow.className = 'hidden flex-wrap items-center gap-1 border-t border-cms-navy/8 pt-1';
    toolbar.append(mainRow, tableRow);
    const surface = document.createElement('div');
    const source = document.createElement('textarea');
    source.hidden = true;
    source.spellcheck = false;
    source.setAttribute('aria-label', 'HTML source');
    source.className = 'block min-h-72 w-full resize-y rounded-b-xl bg-cms-navy px-4 py-3 font-mono text-xs leading-6 text-white outline-none';
    shell.append(toolbar, surface, source);
    let sourceMode = false;

    const editor = new Editor({
        element: surface,
        extensions: [
            StarterKit.configure({
                heading: { levels: [2, 3, 4] },
                // No target/rel: links open in the same tab and the sanitizer drops both anyway.
                link: { openOnClick: false, autolink: true, defaultProtocol: 'https', HTMLAttributes: { target: null, rel: null } },
            }),
            Alignment.configure({ types: ['heading', 'paragraph'], alignments: ['left', 'center', 'right', 'justify'] }),
            Highlight,
            Image.configure({ inline: false, allowBase64: false }),
            Youtube.configure({ nocookie: true, controls: true, width: 640, height: 360 }),
            TableKit.configure({ table: { resizable: false } }),
            Embed,
        ],
        content: textarea.value,
        editorProps: { attributes: { class: editorClass, 'aria-label': 'Content', 'aria-multiline': 'true', role: 'textbox' } },
        onUpdate: ({ editor: current }) => {
            textarea.value = current.isEmpty ? '' : current.getHTML();
            textarea.dispatchEvent(new Event('input', { bubbles: true }));
        },
    });

    const chain = () => editor.chain().focus();

    /**
     * An inserted image or embed stays selected, so the next insert would replace it.
     * Put the cursor in the paragraph after it instead, adding one when there is none.
     */
    const continueAfterInsertedNode = () => {
        const { selection, doc } = editor.state;
        if (!selection.node) return;
        const after = selection.to;
        if (doc.resolve(after).nodeAfter?.isTextblock) {
            editor.chain().focus().setTextSelection(after + 1).run();
            return;
        }
        editor.chain().focus().insertContentAt(after, { type: 'paragraph' }).setTextSelection(after + 1).run();
    };

    const toggleLink = () => {
        if (editor.isActive('link')) {
            chain().extendMarkRange('link').unsetLink().run();
            return;
        }
        const url = window.prompt('Link URL (https://…, /page or #section)', 'https://');
        if (url === null) return;
        const href = normalizeUrl(url);
        if (href === '' || href === 'https://') return;
        chain().extendMarkRange('link').setLink({ href }).run();
    };

    const hasMediaPicker = Boolean(document.querySelector('[data-media-picker]'));
    const insertImage = () => {
        const insert = (media) => {
            if (!media.mime_type?.startsWith('image/')) {
                window.alert('Choose an image file.');
                return;
            }
            chain().setImage({ src: mediaSource(media.url), alt: media.alt_text || '', title: media.caption || null }).run();
            continueAfterInsertedNode();
        };
        window.dispatchEvent(new CustomEvent('cms:choose-media', { detail: { onSelect: insert } }));
    };

    const editImageAlt = () => {
        const current = editor.getAttributes('image').alt ?? '';
        const alt = window.prompt('Alternative text (describe the image for screen readers and search engines)', current);
        if (alt !== null) chain().updateAttributes('image', { alt: alt.trim() }).run();
    };

    const insertEmbed = () => {
        const input = window.prompt('Paste a link or embed code from YouTube, Vimeo, Google Maps, Spotify, Instagram or TikTok.\nGoogle Maps: Share → Embed a map → Copy HTML.');
        if (!input) return;
        const embed = toEmbed(input);
        if (embed?.provider === 'youtube') {
            if (chain().setYoutubeVideo({ src: embed.src }).run()) continueAfterInsertedNode();
            else window.alert('That is not a YouTube video link.');
            return;
        }
        if (!embed) {
            window.alert('That link cannot be embedded. Use a YouTube, Vimeo, Spotify, Instagram or TikTok link, or the embed code from Google Maps.');
            return;
        }
        chain().insertContent({ type: 'embed', attrs: embed }).run();
        continueAfterInsertedNode();
    };

    const toggleSource = () => {
        sourceMode = !sourceMode;
        if (sourceMode) {
            source.value = formatSource(editor.getHTML());
            source.style.height = `${Math.max(surface.offsetHeight, 288)}px`;
        } else {
            // The editor keeps only what it supports; the server sanitizes the rest on save.
            editor.commands.setContent(source.value, { emitUpdate: true });
        }
        surface.hidden = sourceMode;
        source.hidden = !sourceMode;
        syncToolbar();
        (sourceMode ? source : editor.view.dom).focus();
    };
    // Edits in the HTML view are saved even if the editor is never switched back.
    source.addEventListener('input', () => {
        textarea.value = source.value.trim();
        textarea.dispatchEvent(new Event('input', { bubbles: true }));
    });

    const separator = () => {
        const line = document.createElement('span');
        line.className = 'mx-0.5 h-5 w-px bg-cms-navy/12';
        line.setAttribute('aria-hidden', 'true');
        return line;
    };

    const blockStyle = document.createElement('select');
    blockStyle.className = 'h-8 rounded-lg border border-cms-navy/10 bg-white px-2 text-xs font-semibold text-cms-navy outline-none focus:border-cms-gold';
    blockStyle.setAttribute('aria-label', 'Text style');
    [['paragraph', 'Paragraph'], ['2', 'Heading 2'], ['3', 'Heading 3'], ['4', 'Heading 4']].forEach(([value, label]) => blockStyle.add(new Option(label, value)));
    blockStyle.addEventListener('change', () => {
        if (blockStyle.value === 'paragraph') chain().setParagraph().run();
        else chain().setHeading({ level: Number(blockStyle.value) }).run();
    });

    const mainTools = [
        blockStyle,
        separator(),
        { label: 'B', title: 'Bold', run: () => chain().toggleBold().run(), active: () => editor.isActive('bold'), className: 'font-black' },
        { label: 'I', title: 'Italic', run: () => chain().toggleItalic().run(), active: () => editor.isActive('italic'), className: 'italic' },
        { label: 'U', title: 'Underline', run: () => chain().toggleUnderline().run(), active: () => editor.isActive('underline'), className: 'underline' },
        { label: 'S', title: 'Strikethrough', run: () => chain().toggleStrike().run(), active: () => editor.isActive('strike'), className: 'line-through' },
        { label: 'Mark', title: 'Highlight', run: () => chain().toggleHighlight().run(), active: () => editor.isActive('highlight') },
        { label: 'Link', title: 'Add or remove link', run: toggleLink, active: () => editor.isActive('link') },
        separator(),
        ...[['left', '⇤'], ['center', '↔'], ['right', '⇥'], ['justify', '☰']].map(([alignment, label]) => ({
            label,
            title: `Align ${alignment}`,
            run: () => chain().setTextAlign(alignment).run(),
            active: () => (alignment === 'left' ? !['center', 'right', 'justify'].some((other) => editor.isActive({ textAlign: other })) : editor.isActive({ textAlign: alignment })),
        })),
        separator(),
        { label: '• List', title: 'Bulleted list', run: () => chain().toggleBulletList().run(), active: () => editor.isActive('bulletList') },
        { label: '1. List', title: 'Numbered list', run: () => chain().toggleOrderedList().run(), active: () => editor.isActive('orderedList') },
        { label: '“ Quote', title: 'Quote', run: () => chain().toggleBlockquote().run(), active: () => editor.isActive('blockquote') },
        { label: '</>', title: 'Code block', run: () => chain().toggleCodeBlock().run(), active: () => editor.isActive('codeBlock') },
        { label: '—', title: 'Divider', run: () => chain().setHorizontalRule().run() },
        separator(),
        ...(hasMediaPicker ? [{ label: 'Image', title: 'Insert image from the Media Library', run: insertImage }] : []),
        { label: 'Alt', title: 'Edit image alternative text', run: editImageAlt, visible: () => editor.isActive('image') },
        { label: 'Embed', title: 'Embed YouTube, Vimeo, Google Maps, Spotify, Instagram or TikTok', run: insertEmbed },
        { label: 'Table', title: 'Insert table', run: () => chain().insertTable({ rows: 3, cols: 3, withHeaderRow: true }).run(), enabled: () => !editor.isActive('table') },
        separator(),
        { label: '↶', title: 'Undo', run: () => chain().undo().run(), enabled: () => editor.can().undo() },
        { label: '↷', title: 'Redo', run: () => chain().redo().run(), enabled: () => editor.can().redo() },
        separator(),
        { label: 'HTML', title: 'Edit as HTML', run: () => toggleSource(), active: () => sourceMode, source: true },
    ];

    const tableTools = [
        { label: '+ Row ↑', title: 'Add row above', run: () => chain().addRowBefore().run() },
        { label: '+ Row ↓', title: 'Add row below', run: () => chain().addRowAfter().run() },
        { label: '− Row', title: 'Delete row', run: () => chain().deleteRow().run() },
        separator(),
        { label: '+ Col ←', title: 'Add column left', run: () => chain().addColumnBefore().run() },
        { label: '+ Col →', title: 'Add column right', run: () => chain().addColumnAfter().run() },
        { label: '− Col', title: 'Delete column', run: () => chain().deleteColumn().run() },
        separator(),
        { label: 'Merge', title: 'Merge selected cells', run: () => chain().mergeCells().run(), enabled: () => editor.can().mergeCells() },
        { label: 'Split', title: 'Split cell', run: () => chain().splitCell().run(), enabled: () => editor.can().splitCell() },
        { label: 'Header row', title: 'Toggle header row', run: () => chain().toggleHeaderRow().run() },
        separator(),
        { label: 'Delete table', title: 'Delete table', run: () => chain().deleteTable().run(), className: 'text-red-700' },
    ];

    const renderTools = (tools, row) => tools.map((tool) => {
        if (tool instanceof HTMLElement) {
            row.append(tool);
            return null;
        }
        const button = document.createElement('button');
        button.type = 'button';
        button.className = `${buttonClass} ${tool.className ?? ''}`;
        button.textContent = tool.label;
        button.title = tool.title;
        button.setAttribute('aria-label', tool.title);
        // Keep the selection in the editor while clicking the toolbar.
        button.addEventListener('mousedown', (event) => event.preventDefault());
        button.addEventListener('click', tool.run);
        row.append(button);

        return { tool, button };
    }).filter(Boolean);

    const buttons = [...renderTools(mainTools, mainRow), ...renderTools(tableTools, tableRow)];

    function syncToolbar() {
        buttons.forEach(({ tool, button }) => {
            if (tool.active) button.setAttribute('aria-pressed', String(tool.active()));
            // In the HTML view only the HTML toggle works; formatting applies to the visual editor.
            button.disabled = sourceMode ? !tool.source : (tool.enabled ? !tool.enabled() : false);
            if (tool.visible) button.hidden = sourceMode || !tool.visible();
        });
        blockStyle.disabled = sourceMode;
        const level = editor.getAttributes('heading').level;
        blockStyle.value = editor.isActive('heading') && level ? String(level) : 'paragraph';
        const inTable = !sourceMode && editor.isActive('table');
        tableRow.classList.toggle('hidden', !inTable);
        tableRow.classList.toggle('flex', inTable);
    }
    editor.on('selectionUpdate', syncToolbar);
    editor.on('transaction', syncToolbar);
    syncToolbar();

    textarea.hidden = true;
    textarea.after(shell);

    // Free the editor when its block is removed from the page.
    const observer = new MutationObserver(() => {
        if (!shell.isConnected) {
            observer.disconnect();
            editor.destroy();
        }
    });
    observer.observe(document.body, { childList: true, subtree: true });

    return editor;
}
