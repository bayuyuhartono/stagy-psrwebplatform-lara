---
paths:
  - 'app/Features/System/**,app/Http/Resources/Api/V1/System*Resource.php'
---

# Http Resources Api V1

## Keep operational APIs read-only and redacted
System settings and health endpoints are diagnostic only. Expose driver names, versions, readiness, counts, and generic failure messages; never expose application keys, hosts, usernames, passwords, tokens, exception details, or other connection secrets.
