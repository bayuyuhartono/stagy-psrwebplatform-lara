<?php

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
        'fields' => [
            'heading' => ['type' => 'string', 'required' => true],
            'subheading' => ['type' => 'string', 'required' => false],
            'media_id' => ['type' => 'uuid', 'required' => false],
            'primary_action' => ['type' => 'action', 'required' => false],
        ],
        'rules' => [
            'data' => ['required', 'array:heading,subheading,media_id,primary_action'],
            'data.heading' => ['required', 'string', 'max:255'],
            'data.subheading' => ['nullable', 'string', 'max:1000'],
            'data.media_id' => ['nullable', 'uuid', 'exists:media,id'],
            'data.primary_action' => ['nullable', 'array:label,url'],
            'data.primary_action.label' => ['required_with:data.primary_action', 'string', 'max:100'],
            'data.primary_action.url' => ['required_with:data.primary_action', 'url:http,https', 'max:2048'],
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
        'fields' => [
            'heading' => ['type' => 'string', 'required' => true],
            'body' => ['type' => 'string', 'required' => false],
            'label' => ['type' => 'string', 'required' => true],
            'url' => ['type' => 'url', 'required' => true],
        ],
        'rules' => [
            'data' => ['required', 'array:heading,body,label,url'],
            'data.heading' => ['required', 'string', 'max:255'],
            'data.body' => ['nullable', 'string', 'max:2000'],
            'data.label' => ['required', 'string', 'max:100'],
            'data.url' => ['required', 'url:http,https', 'max:2048'],
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
];
