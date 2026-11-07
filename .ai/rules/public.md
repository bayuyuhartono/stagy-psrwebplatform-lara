---
paths:
  - 'resources/views/{public,components/public}/**'
---

# Public

## Public website uses an independent palette
Public-facing templates must use the `public-ink`, `public-surface`, `public-accent`, and `public-paper` tokens from resources/css/theme.css. Keep the `cms-*` palette exclusive to the CMS interface; do not mix CMS brand tokens into public pages.
