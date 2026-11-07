<?php

/*
|--------------------------------------------------------------------------
| Content blocks
|--------------------------------------------------------------------------
|
| Every block type a page or post may contain. `fields` describes the block
| for the CMS editor and API clients; `rules` validates its `data` payload.
|
| Blocks marked `'editor' => 'schema'` get a form generated from `fields`:
|   string · text (multi-line) · link (see the content_link rule) · media
|   select (`options`) · boolean · action (label + link) · list (one per line)
|   repeater (rows of the nested `fields`; string, text, link, select only)
|
| In headings, *asterisks* around a word mark it as highlighted.
|
| The pricing_* and support_tiers blocks carry only their framing text; the
| plans, comparison rows and add-ons come from the Pricing module.
|
*/

$highlightHelp = 'Wrap a word in *asterisks* to highlight it.';
$linkHelp = 'A full URL, a path like /harga, an #anchor on this page, or "whatsapp" for the WhatsApp chat.';
$actionRules = fn (string $key): array => [
    "data.{$key}" => ['nullable', 'array:label,url'],
    "data.{$key}.label" => ["required_with:data.{$key}", 'string', 'max:100'],
    "data.{$key}.url" => ["required_with:data.{$key}", 'string', 'max:2048', 'content_link'],
];

return [
    'rich_text' => [
        'label' => 'Rich Text',
        'fields' => [
            'content' => ['type' => 'string', 'required' => true],
        ],
        'rules' => [
            'data' => ['required', 'array:content'],
            'data.content' => ['required', 'string', 'max:100000'],
        ],
    ],

    'hero' => [
        'label' => 'Hero',
        'editor' => 'schema',
        'fields' => [
            'badge' => ['type' => 'string', 'label' => 'Badge', 'required' => false],
            'heading' => ['type' => 'string', 'label' => 'Heading', 'required' => true, 'help' => $highlightHelp],
            'subheading' => ['type' => 'text', 'label' => 'Subheading', 'required' => false],
            'primary_action' => ['type' => 'action', 'label' => 'Primary button', 'required' => false, 'help' => $linkHelp],
            'secondary_action' => ['type' => 'action', 'label' => 'Secondary button', 'required' => false],
            'trust_points' => ['type' => 'list', 'label' => 'Trust points', 'required' => false, 'help' => 'Short reassurances shown with a check mark. One per line.'],
            'visual' => ['type' => 'select', 'label' => 'Visual', 'required' => false, 'default' => 'ticket', 'options' => ['ticket' => 'E-ticket mock-up', 'media' => 'Image', 'none' => 'None']],
            'media_id' => ['type' => 'media', 'label' => 'Image (when Visual is Image)', 'required' => false],
            'ticket_label' => ['type' => 'string', 'label' => 'Ticket · label', 'required' => false, 'default' => 'E-ticket · VIP'],
            'ticket_event' => ['type' => 'string', 'label' => 'Ticket · event name', 'required' => false],
            'ticket_guest' => ['type' => 'string', 'label' => 'Ticket · guest name', 'required' => false],
            'ticket_detail' => ['type' => 'string', 'label' => 'Ticket · guest detail', 'required' => false],
            'ticket_time' => ['type' => 'string', 'label' => 'Ticket · date & time', 'required' => false],
            'ticket_gate' => ['type' => 'string', 'label' => 'Ticket · gate', 'required' => false],
        ],
        'rules' => [
            'data' => ['required', 'array:badge,heading,subheading,media_id,primary_action,secondary_action,trust_points,visual,ticket_label,ticket_event,ticket_guest,ticket_detail,ticket_time,ticket_gate'],
            'data.badge' => ['nullable', 'string', 'max:120'],
            'data.heading' => ['required', 'string', 'max:255'],
            'data.subheading' => ['nullable', 'string', 'max:1000'],
            'data.media_id' => ['nullable', 'uuid', 'exists:media,id'],
            ...$actionRules('primary_action'),
            ...$actionRules('secondary_action'),
            'data.trust_points' => ['nullable', 'array', 'max:6'],
            'data.trust_points.*' => ['string', 'max:120'],
            'data.visual' => ['nullable', 'in:ticket,media,none'],
            'data.ticket_label' => ['nullable', 'string', 'max:60'],
            'data.ticket_event' => ['nullable', 'string', 'max:120'],
            'data.ticket_guest' => ['nullable', 'string', 'max:80'],
            'data.ticket_detail' => ['nullable', 'string', 'max:80'],
            'data.ticket_time' => ['nullable', 'string', 'max:40'],
            'data.ticket_gate' => ['nullable', 'string', 'max:40'],
        ],
    ],

    'image' => [
        'label' => 'Image',
        'fields' => [
            'media_id' => ['type' => 'uuid', 'required' => true],
            'alt' => ['type' => 'string', 'required' => false],
            'caption' => ['type' => 'string', 'required' => false],
        ],
        'rules' => [
            'data' => ['required', 'array:media_id,alt,caption'],
            'data.media_id' => ['required', 'uuid', 'exists:media,id'],
            'data.alt' => ['nullable', 'string', 'max:255'],
            'data.caption' => ['nullable', 'string', 'max:2000'],
        ],
    ],

    'call_to_action' => [
        'label' => 'Call to Action',
        'editor' => 'schema',
        'fields' => [
            'heading' => ['type' => 'string', 'label' => 'Heading', 'required' => true, 'help' => $highlightHelp],
            'body' => ['type' => 'text', 'label' => 'Body', 'required' => false],
            'label' => ['type' => 'string', 'label' => 'Button label', 'required' => true],
            'url' => ['type' => 'link', 'label' => 'Button link', 'required' => true, 'help' => $linkHelp],
            'secondary_action' => ['type' => 'action', 'label' => 'Second button', 'required' => false],
        ],
        'rules' => [
            'data' => ['required', 'array:heading,body,label,url,secondary_action'],
            'data.heading' => ['required', 'string', 'max:255'],
            'data.body' => ['nullable', 'string', 'max:2000'],
            'data.label' => ['required', 'string', 'max:100'],
            'data.url' => ['required', 'string', 'max:2048', 'content_link'],
            ...$actionRules('secondary_action'),
        ],
    ],

    'feature_grid' => [
        'label' => 'Feature Grid',
        'fields' => [
            'items' => ['type' => 'array', 'required' => true, 'min' => 1, 'max' => 12],
        ],
        'rules' => [
            'data' => ['required', 'array:items'],
            'data.items' => ['required', 'array', 'min:1', 'max:12'],
            'data.items.*' => ['required', 'array:title,description,icon'],
            'data.items.*.title' => ['required', 'string', 'max:255'],
            'data.items.*.description' => ['nullable', 'string', 'max:1000'],
            'data.items.*.icon' => ['nullable', 'string', 'max:100'],
        ],
    ],

    'page_header' => [
        'label' => 'Page Header',
        'editor' => 'schema',
        'fields' => [
            'eyebrow' => ['type' => 'string', 'label' => 'Eyebrow', 'required' => false],
            'heading' => ['type' => 'string', 'label' => 'Heading', 'required' => true, 'help' => $highlightHelp],
            'intro' => ['type' => 'text', 'label' => 'Introduction', 'required' => false],
            'links' => ['type' => 'repeater', 'label' => 'Jump links', 'item_label' => 'Link', 'required' => false, 'help' => 'Optional chips that jump to sections, e.g. #venue.', 'fields' => [
                'label' => ['type' => 'string', 'label' => 'Label', 'required' => true],
                'url' => ['type' => 'link', 'label' => 'Link', 'required' => true],
            ]],
        ],
        'rules' => [
            'data' => ['required', 'array:eyebrow,heading,intro,links'],
            'data.eyebrow' => ['nullable', 'string', 'max:80'],
            'data.heading' => ['required', 'string', 'max:255'],
            'data.intro' => ['nullable', 'string', 'max:1000'],
            'data.links' => ['nullable', 'array', 'max:8'],
            'data.links.*' => ['array:label,url'],
            'data.links.*.label' => ['required', 'string', 'max:60'],
            'data.links.*.url' => ['required', 'string', 'max:2048', 'content_link'],
        ],
    ],

    'feature_cards' => [
        'label' => 'Feature Cards',
        'editor' => 'schema',
        'fields' => [
            'eyebrow' => ['type' => 'string', 'label' => 'Eyebrow', 'required' => false],
            'heading' => ['type' => 'string', 'label' => 'Heading', 'required' => true, 'help' => $highlightHelp],
            'link' => ['type' => 'action', 'label' => 'Section link', 'required' => false],
            'items' => ['type' => 'repeater', 'label' => 'Cards', 'item_label' => 'Card', 'required' => true, 'help' => 'Cards are numbered automatically.', 'fields' => [
                'title' => ['type' => 'string', 'label' => 'Title', 'required' => true],
                'description' => ['type' => 'text', 'label' => 'Description', 'required' => false],
                'url' => ['type' => 'link', 'label' => 'Link', 'required' => false],
            ]],
        ],
        'rules' => [
            'data' => ['required', 'array:eyebrow,heading,link,items'],
            'data.eyebrow' => ['nullable', 'string', 'max:80'],
            'data.heading' => ['required', 'string', 'max:255'],
            ...$actionRules('link'),
            'data.items' => ['required', 'array', 'min:1', 'max:8'],
            'data.items.*' => ['array:title,description,url'],
            'data.items.*.title' => ['required', 'string', 'max:120'],
            'data.items.*.description' => ['nullable', 'string', 'max:500'],
            'data.items.*.url' => ['nullable', 'string', 'max:2048', 'content_link'],
        ],
    ],

    'steps' => [
        'label' => 'Steps',
        'editor' => 'schema',
        'fields' => [
            'eyebrow' => ['type' => 'string', 'label' => 'Eyebrow', 'required' => false],
            'heading' => ['type' => 'string', 'label' => 'Heading', 'required' => false, 'help' => $highlightHelp],
            'link' => ['type' => 'action', 'label' => 'Section link', 'required' => false],
            'variant' => ['type' => 'select', 'label' => 'Layout', 'required' => false, 'default' => 'compact', 'options' => ['compact' => 'Compact row', 'detailed' => 'Detailed cards (you / us)']],
            'items' => ['type' => 'repeater', 'label' => 'Steps', 'item_label' => 'Step', 'required' => true, 'help' => 'Steps are numbered automatically. "You" and "Us" are used by the detailed layout.', 'fields' => [
                'title' => ['type' => 'string', 'label' => 'Title', 'required' => true],
                'you' => ['type' => 'text', 'label' => 'What the client does', 'required' => false],
                'us' => ['type' => 'text', 'label' => 'What we do', 'required' => false],
            ]],
        ],
        'rules' => [
            'data' => ['required', 'array:eyebrow,heading,link,variant,items'],
            'data.eyebrow' => ['nullable', 'string', 'max:80'],
            'data.heading' => ['nullable', 'string', 'max:255'],
            ...$actionRules('link'),
            'data.variant' => ['nullable', 'in:compact,detailed'],
            'data.items' => ['required', 'array', 'min:1', 'max:8'],
            'data.items.*' => ['array:title,you,us'],
            'data.items.*.title' => ['required', 'string', 'max:160'],
            'data.items.*.you' => ['nullable', 'string', 'max:500'],
            'data.items.*.us' => ['nullable', 'string', 'max:500'],
        ],
    ],

    'feature_group' => [
        'label' => 'Feature Group',
        'editor' => 'schema',
        'fields' => [
            'anchor' => ['type' => 'string', 'label' => 'Anchor', 'required' => false, 'help' => 'Lets other links jump here, e.g. "venue" for #venue.'],
            'label' => ['type' => 'string', 'label' => 'Stage label', 'required' => false],
            'heading' => ['type' => 'string', 'label' => 'Heading', 'required' => true, 'help' => $highlightHelp],
            'description' => ['type' => 'text', 'label' => 'Description', 'required' => false],
            'points' => ['type' => 'list', 'label' => 'Checklist', 'required' => true, 'help' => 'One feature per line.'],
            'media_id' => ['type' => 'media', 'label' => 'Screenshot', 'required' => false],
            'media_placeholder' => ['type' => 'string', 'label' => 'Placeholder text (shown until a screenshot is chosen)', 'required' => false],
        ],
        'rules' => [
            'data' => ['required', 'array:anchor,label,heading,description,points,media_id,media_placeholder'],
            'data.anchor' => ['nullable', 'string', 'max:60', 'regex:/^[A-Za-z][\w-]*$/'],
            'data.label' => ['nullable', 'string', 'max:80'],
            'data.heading' => ['required', 'string', 'max:255'],
            'data.description' => ['nullable', 'string', 'max:1000'],
            'data.points' => ['required', 'array', 'min:1', 'max:12'],
            'data.points.*' => ['string', 'max:200'],
            'data.media_id' => ['nullable', 'uuid', 'exists:media,id'],
            'data.media_placeholder' => ['nullable', 'string', 'max:160'],
        ],
    ],

    'faq' => [
        'label' => 'FAQ',
        'editor' => 'schema',
        'fields' => [
            'eyebrow' => ['type' => 'string', 'label' => 'Eyebrow', 'required' => false],
            'heading' => ['type' => 'string', 'label' => 'Heading', 'required' => false, 'help' => $highlightHelp],
            'intro' => ['type' => 'text', 'label' => 'Introduction', 'required' => false],
            'show_whatsapp' => ['type' => 'boolean', 'label' => 'Show a "Chat WhatsApp" button', 'required' => false],
            'items' => ['type' => 'repeater', 'label' => 'Questions', 'item_label' => 'Question', 'required' => true, 'fields' => [
                'question' => ['type' => 'string', 'label' => 'Question', 'required' => true],
                'answer' => ['type' => 'text', 'label' => 'Answer', 'required' => true],
            ]],
        ],
        'rules' => [
            'data' => ['required', 'array:eyebrow,heading,intro,show_whatsapp,items'],
            'data.eyebrow' => ['nullable', 'string', 'max:80'],
            'data.heading' => ['nullable', 'string', 'max:255'],
            'data.intro' => ['nullable', 'string', 'max:1000'],
            'data.show_whatsapp' => ['nullable', 'boolean'],
            'data.items' => ['required', 'array', 'min:1', 'max:40'],
            'data.items.*' => ['array:question,answer'],
            'data.items.*.question' => ['required', 'string', 'max:255'],
            'data.items.*.answer' => ['required', 'string', 'max:3000'],
        ],
    ],

    'legal_sections' => [
        'label' => 'Legal Document',
        'editor' => 'schema',
        'fields' => [
            'eyebrow' => ['type' => 'string', 'label' => 'Eyebrow', 'required' => false],
            'heading' => ['type' => 'string', 'label' => 'Heading', 'required' => true],
            'updated' => ['type' => 'string', 'label' => 'Last updated', 'required' => false, 'help' => 'Shown as written, e.g. "1 November 2026".'],
            'intro' => ['type' => 'text', 'label' => 'Opening paragraph', 'required' => false],
            'sections' => ['type' => 'repeater', 'label' => 'Sections', 'item_label' => 'Section', 'required' => true, 'help' => 'Sections are numbered and listed in a table of contents automatically.', 'fields' => [
                'title' => ['type' => 'string', 'label' => 'Title', 'required' => true],
                'body' => ['type' => 'text', 'label' => 'Text', 'required' => true],
            ]],
            'closing' => ['type' => 'text', 'label' => 'Closing note', 'required' => false],
        ],
        'rules' => [
            'data' => ['required', 'array:eyebrow,heading,updated,intro,sections,closing'],
            'data.eyebrow' => ['nullable', 'string', 'max:80'],
            'data.heading' => ['required', 'string', 'max:255'],
            'data.updated' => ['nullable', 'string', 'max:80'],
            'data.intro' => ['nullable', 'string', 'max:5000'],
            'data.sections' => ['required', 'array', 'min:1', 'max:40'],
            'data.sections.*' => ['array:title,body'],
            'data.sections.*.title' => ['required', 'string', 'max:160'],
            'data.sections.*.body' => ['required', 'string', 'max:20000'],
            'data.closing' => ['nullable', 'string', 'max:2000'],
        ],
    ],

    'pricing_cards' => [
        'label' => 'Pricing · Plan Cards',
        'editor' => 'schema',
        'fields' => [
            'eyebrow' => ['type' => 'string', 'label' => 'Eyebrow', 'required' => false],
            'heading' => ['type' => 'string', 'label' => 'Heading', 'required' => false, 'help' => $highlightHelp],
            'intro' => ['type' => 'text', 'label' => 'Introduction', 'required' => false],
            'link' => ['type' => 'action', 'label' => 'Section link', 'required' => false],
            'variant' => ['type' => 'select', 'label' => 'Layout', 'required' => false, 'default' => 'full', 'options' => ['full' => 'Full cards with features', 'summary' => 'Compact summary']],
            'note' => ['type' => 'text', 'label' => 'Note under the cards', 'required' => false],
        ],
        'rules' => [
            'data' => ['required', 'array:eyebrow,heading,intro,link,variant,note'],
            'data.eyebrow' => ['nullable', 'string', 'max:80'],
            'data.heading' => ['nullable', 'string', 'max:255'],
            'data.intro' => ['nullable', 'string', 'max:1000'],
            ...$actionRules('link'),
            'data.variant' => ['nullable', 'in:full,summary'],
            'data.note' => ['nullable', 'string', 'max:500'],
        ],
    ],

    'pricing_table' => [
        'label' => 'Pricing · Comparison Table',
        'editor' => 'schema',
        'fields' => [
            'eyebrow' => ['type' => 'string', 'label' => 'Eyebrow', 'required' => false],
            'heading' => ['type' => 'string', 'label' => 'Heading', 'required' => false, 'help' => $highlightHelp],
            'note' => ['type' => 'text', 'label' => 'Note under the table', 'required' => false],
            'show_included_in_all' => ['type' => 'boolean', 'label' => 'List features every plan includes', 'required' => false, 'default' => true],
        ],
        'rules' => [
            'data' => ['required', 'array:eyebrow,heading,note,show_included_in_all'],
            'data.eyebrow' => ['nullable', 'string', 'max:80'],
            'data.heading' => ['nullable', 'string', 'max:255'],
            'data.note' => ['nullable', 'string', 'max:500'],
            'data.show_included_in_all' => ['nullable', 'boolean'],
        ],
    ],

    'pricing_addons' => [
        'label' => 'Pricing · Add-ons',
        'editor' => 'schema',
        'fields' => [
            'eyebrow' => ['type' => 'string', 'label' => 'Eyebrow', 'required' => false],
            'heading' => ['type' => 'string', 'label' => 'Heading', 'required' => false, 'help' => $highlightHelp],
            'intro' => ['type' => 'text', 'label' => 'Introduction', 'required' => false],
        ],
        'rules' => [
            'data' => ['required', 'array:eyebrow,heading,intro'],
            'data.eyebrow' => ['nullable', 'string', 'max:80'],
            'data.heading' => ['nullable', 'string', 'max:255'],
            'data.intro' => ['nullable', 'string', 'max:1000'],
        ],
    ],

    'support_tiers' => [
        'label' => 'Pricing · On-site Support',
        'editor' => 'schema',
        'fields' => [
            'eyebrow' => ['type' => 'string', 'label' => 'Eyebrow', 'required' => false],
            'heading' => ['type' => 'string', 'label' => 'Heading', 'required' => true, 'help' => $highlightHelp],
            'link' => ['type' => 'action', 'label' => 'Link', 'required' => false],
        ],
        'rules' => [
            'data' => ['required', 'array:eyebrow,heading,link'],
            'data.eyebrow' => ['nullable', 'string', 'max:80'],
            'data.heading' => ['required', 'string', 'max:255'],
            ...$actionRules('link'),
        ],
    ],

    'demo_form' => [
        'label' => 'Demo Request Form',
        'editor' => 'schema',
        'fields' => [
            'eyebrow' => ['type' => 'string', 'label' => 'Eyebrow', 'required' => false],
            'heading' => ['type' => 'string', 'label' => 'Heading', 'required' => true, 'help' => $highlightHelp],
            'intro' => ['type' => 'text', 'label' => 'Introduction', 'required' => false],
            'next_steps' => ['type' => 'list', 'label' => 'What happens next', 'required' => false, 'help' => 'Numbered automatically. One step per line.'],
            'chat_prompt' => ['type' => 'string', 'label' => 'WhatsApp prompt', 'required' => false, 'help' => 'Shown above a "Chat WhatsApp" button when a number is configured.'],
            'success_message' => ['type' => 'text', 'label' => 'Message after sending', 'required' => false],
            'privacy_url' => ['type' => 'link', 'label' => 'Privacy policy link', 'required' => false],
        ],
        'rules' => [
            'data' => ['required', 'array:eyebrow,heading,intro,next_steps,chat_prompt,success_message,privacy_url'],
            'data.eyebrow' => ['nullable', 'string', 'max:80'],
            'data.heading' => ['required', 'string', 'max:255'],
            'data.intro' => ['nullable', 'string', 'max:1000'],
            'data.next_steps' => ['nullable', 'array', 'max:6'],
            'data.next_steps.*' => ['string', 'max:200'],
            'data.chat_prompt' => ['nullable', 'string', 'max:120'],
            'data.success_message' => ['nullable', 'string', 'max:1000'],
            'data.privacy_url' => ['nullable', 'string', 'max:2048', 'content_link'],
        ],
    ],
];
