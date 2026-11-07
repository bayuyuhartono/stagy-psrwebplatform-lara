---
paths:
  - 'app/Features/Seo/**,app/Observers/SitemapCacheObserver.php,app/Models/{Page,Post,Service,Portfolio,Seo}.php'
---

# Models

## Keep sitemap eligibility and cache invalidation consistent
Include only published content whose published_at is not future and whose SEO is not noindex. Sitemap canonicals prefer explicit SEO canonical_url, otherwise use the standard public path. Page/Post/Service/Portfolio and Seo save/delete/restore events must invalidate seo:sitemap.xml.
