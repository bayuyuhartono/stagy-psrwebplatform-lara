---
paths:
  - 'app/Features/Search/**'
---

# Search

## Global search enforces two permission layers
Admin global search requires global-search.view, then searches only modules for which the actor also has the module's *.view permission. Return only the shared summary fields; never expose content bodies, contact messages, internal notes, credentials, or other sensitive fields.
