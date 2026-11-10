<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">
        <title>{{ $title ?? 'CMS Login' }} · {{ config('app.name') }}</title>
        <link rel="icon" type="image/png" sizes="32x32" href="{{ asset('favicons/cms/favicon-32.png') }}">
        <link rel="icon" type="image/png" sizes="16x16" href="{{ asset('favicons/cms/favicon-16.png') }}">
        <link rel="icon" type="image/png" sizes="512x512" href="{{ asset('favicons/cms/favicon-512.png') }}">
        <link rel="apple-touch-icon" sizes="180x180" href="{{ asset('favicons/cms/apple-touch-icon.png') }}">
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="min-h-screen">
        {{ $slot }}
    </body>
</html>
