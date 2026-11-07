---
paths:
  - 'app/Features/EmailSettings/**,app/Models/EmailSetting.php,app/Http/Resources/Api/V1/EmailSettingResource.php'
---

# Api V1

## Protect SMTP credentials
Store SMTP username and password with encrypted casts. Never return credentials in API resources or write their values to audit logs; expose only a credentials_configured boolean.
