import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import { local } from 'laravel-vite-plugin/fonts';
import tailwindcss from '@tailwindcss/vite';

export default defineConfig({
    plugins: [
        laravel({
            input: [
                'resources/css/app.css',
                'resources/js/app.js',
                'resources/themes/stagy/stagy.css',
                'resources/themes/stagy/stagy.js',
            ],
            refresh: true,
            fonts: [
                // Self-hosted WOFF2 (latin subset, SIL OFL 1.1). Remote providers also emit a WOFF
                // @font-face per variant, which wins over the preloaded WOFF2 and downloads twice.
                local('Instrument Sans', {
                    // Listed explicitly: the plugin does not infer weights from these file names.
                    variants: [400, 500, 600].flatMap((weight) => ['normal', 'italic'].map((style) => ({
                        src: `resources/fonts/instrument-sans/instrument-sans-${weight}-${style}.woff2`,
                        weight,
                        style,
                    }))),
                    fallbacks: ['ui-sans-serif', 'system-ui', 'sans-serif'],
                    // Regular is what paragraphs need first; other weights load when used.
                    preload: [{ weight: 400, style: 'normal' }],
                }),
                // The Stagy display face. Declared as weight 400 (as the theme always has) so existing font-weight utilities render unchanged.
                local('Horizon', {
                    src: 'resources/themes/stagy/assets/fonts/horizon.woff2',
                    fallbacks: ['ui-sans-serif', 'system-ui', 'sans-serif'],
                }),
            ],
        }),
        tailwindcss(),
    ],
    build: {
        // Theme images are referenced from Blade via Vite::asset(), which needs a real file in the manifest.
        assetsInlineLimit: (filePath) => (filePath.includes('/resources/themes/') ? false : undefined),
    },
    server: {
        watch: {
            ignored: ['**/storage/framework/views/**'],
        },
    },
});
