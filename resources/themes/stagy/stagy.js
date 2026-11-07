// Emit the theme's static images so Blade can reference them with Vite::asset().
// Eager URL imports survive tree-shaking; vite.config.js keeps them from being inlined.
import.meta.glob('./assets/images/**', { eager: true, query: '?url', import: 'default' });
