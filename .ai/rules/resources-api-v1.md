---
paths:
  - 'app/Features/Dashboard/**,app/Http/Resources/Api/V1/DashboardResource.php'
---

# Resources Api V1

## Keep dashboard aggregates efficient and activity safe
Compute dashboard counts and sums in database aggregate queries; do not load full content collections. Recent activity widgets may expose event, actor, target, and timestamp only—never old/new audit payloads, IP addresses, or user agents.
