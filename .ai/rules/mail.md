---
paths:
  - 'app/Features/ContactMessages/**,app/Jobs/SendContactMessageEmails.php,app/Mail/ContactMessage*.php'
---

# Mail

## Queue contact email delivery after commit
Contact submission persists first, then dispatches SendContactMessageEmails after commit. The job loads current email settings, applies retries/backoff, and skips delivery when email is disabled.
