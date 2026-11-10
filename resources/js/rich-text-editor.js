import { Editor } from '@tiptap/core';
import { Markdown } from '@tiptap/markdown';
import StarterKit from '@tiptap/starter-kit';

/**
 * WYSIWYG editing for the rich_text block. The block is still stored as
 * Markdown: the original textarea stays in the form (hidden) as the value the
 * block serializer reads, and every edit writes the editor's Markdown back to it.
 * The public site keeps rendering it with Str::markdown and stripping raw HTML.
 *
 * Only what that Markdown round-trips is offered: H2/H3, bold, italic, lists,
 * quotes and links.
 */
const buttonClass = 'grid h-8 min-w-8 place-items-center rounded-lg px-2 text-xs font-bold text-cms-navy/70 hover:bg-cms-canvas hover:text-cms-navy disabled:opacity-30 aria-pressed:bg-cms-navy aria-pressed:text-white';

const editorClass = [
    'min-h-72 max-w-none px-4 py-3 text-sm leading-7 text-cms-navy outline-none',
    '[&_a]:text-cms-green [&_a]:underline [&_blockquote]:my-3 [&_blockquote]:border-l-2 [&_blockquote]:border-cms-gold [&_blockquote]:pl-4 [&_blockquote]:text-cms-navy/60',
    '[&_h2]:mt-5 [&_h2]:mb-2 [&_h2]:text-xl [&_h2]:font-bold [&_h3]:mt-4 [&_h3]:mb-1.5 [&_h3]:text-base [&_h3]:font-bold',
    '[&_li]:mt-1 [&_li_p]:my-0 [&_ol]:list-decimal [&_ol]:pl-6 [&_p]:my-2 [&_ul]:list-disc [&_ul]:pl-6 [&>*:first-child]:mt-0',
].join(' ');

const normalizeUrl = (url) => {
    const trimmed = url.trim();
    if (trimmed === '' || /^(https?:|mailto:|tel:|\/|#)/i.test(trimmed)) return trimmed;

    return `https://${trimmed}`;
};

/**
 * The serializer writes a typed "&" as "&amp;". Both render the same, but the
 * stored text is also read raw (excerpts, search, reading time), so keep a bare
 * "&" unless it would then read as an entity such as "&lt;".
 */
const toStoredMarkdown = (markdown) => markdown.replace(/&amp;(?![a-zA-Z][a-zA-Z0-9]*;|#[0-9]+;|#x[0-9a-fA-F]+;)/g, '&').trimEnd();

export function mountRichTextEditor(textarea) {
    const shell = document.createElement('div');
    shell.className = 'overflow-hidden rounded-xl border border-cms-navy/10 bg-white focus-within:border-cms-gold';
    const toolbar = document.createElement('div');
    toolbar.className = 'flex flex-wrap items-center gap-1 border-b border-cms-navy/8 bg-cms-canvas/40 p-1.5';
    toolbar.setAttribute('role', 'toolbar');
    toolbar.setAttribute('aria-label', 'Text formatting');
    const surface = document.createElement('div');
    shell.append(toolbar, surface);

    const editor = new Editor({
        element: surface,
        extensions: [
            StarterKit.configure({
                heading: { levels: [2, 3] },
                code: false,
                codeBlock: false,
                horizontalRule: false,
                strike: false,
                underline: false,
                link: { openOnClick: false, autolink: true, defaultProtocol: 'https' },
            }),
            Markdown,
        ],
        content: textarea.value,
        contentType: 'markdown',
        editorProps: { attributes: { class: editorClass, 'aria-label': 'Content', 'aria-multiline': 'true', role: 'textbox' } },
        onUpdate: ({ editor: current }) => {
            textarea.value = current.isEmpty ? '' : toStoredMarkdown(current.getMarkdown());
            textarea.dispatchEvent(new Event('input', { bubbles: true }));
        },
    });

    const toggleLink = () => {
        if (editor.isActive('link')) {
            editor.chain().focus().extendMarkRange('link').unsetLink().run();
            return;
        }
        const url = window.prompt('Link URL (https://…, /page or #section)', 'https://');
        if (url === null) return;
        const href = normalizeUrl(url);
        if (href === '' || href === 'https://') return;
        editor.chain().focus().extendMarkRange('link').setLink({ href }).run();
    };

    const tools = [
        { label: 'H2', title: 'Subheading', run: (chain) => chain.toggleHeading({ level: 2 }), active: () => editor.isActive('heading', { level: 2 }) },
        { label: 'H3', title: 'Smaller subheading', run: (chain) => chain.toggleHeading({ level: 3 }), active: () => editor.isActive('heading', { level: 3 }) },
        { label: 'B', title: 'Bold', run: (chain) => chain.toggleBold(), active: () => editor.isActive('bold'), className: 'font-black' },
        { label: 'I', title: 'Italic', run: (chain) => chain.toggleItalic(), active: () => editor.isActive('italic'), className: 'italic' },
        { label: '• List', title: 'Bulleted list', run: (chain) => chain.toggleBulletList(), active: () => editor.isActive('bulletList') },
        { label: '1. List', title: 'Numbered list', run: (chain) => chain.toggleOrderedList(), active: () => editor.isActive('orderedList') },
        { label: '“ Quote', title: 'Quote', run: (chain) => chain.toggleBlockquote(), active: () => editor.isActive('blockquote') },
        { label: 'Link', title: 'Add or remove link', onClick: toggleLink, active: () => editor.isActive('link') },
        { label: '↶', title: 'Undo', run: (chain) => chain.undo(), enabled: () => editor.can().undo() },
        { label: '↷', title: 'Redo', run: (chain) => chain.redo(), enabled: () => editor.can().redo() },
    ];

    const buttons = tools.map((tool) => {
        const button = document.createElement('button');
        button.type = 'button';
        button.className = `${buttonClass} ${tool.className ?? ''}`;
        button.textContent = tool.label;
        button.title = tool.title;
        button.setAttribute('aria-label', tool.title);
        // Keep the selection in the editor while clicking the toolbar.
        button.addEventListener('mousedown', (event) => event.preventDefault());
        button.addEventListener('click', () => (tool.onClick ? tool.onClick() : tool.run(editor.chain().focus()).run()));
        toolbar.append(button);

        return { tool, button };
    });

    const syncToolbar = () => buttons.forEach(({ tool, button }) => {
        if (tool.active) button.setAttribute('aria-pressed', String(tool.active()));
        if (tool.enabled) button.disabled = !tool.enabled();
    });
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
