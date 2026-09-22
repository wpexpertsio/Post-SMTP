# MailChannels integration

Run the offline regression checks from the repository root:

```sh
php tests/mailchannels.php
```

On PHP 8.2+, the checks use the actual bundled SDK and a fake PSR-18 HTTP
client. They cover payload serialization, recipients, attachments, errors,
credential handling, transport readiness, and wizard rendering. On older PHP,
the same script checks the compatibility guard without loading the SDK.
No credentials, WordPress installation, network requests, or test dependencies
are required. WordPress functions are stubbed; this is not a browser test.

Before release, on WordPress with PHP 8.2+ and an authorized MailChannels domain:

1. Select MailChannels in the setup wizard, save an API key with `api` scope,
   then reopen Account settings. Save again without changing the masked key.
2. Send a Post SMTP test email and confirm both the transcript and receipt.
3. Send a `wp_mail()` message with HTML, Cc, Bcc, Reply-To and a file attachment;
   verify recipient visibility, reply destination and attachment contents.
4. Use an invalid key and confirm failure appears in the existing email log.
5. Switch to another mailer and verify its settings and delivery still work.
6. On PHP below 8.2, select MailChannels and confirm the PHP requirement is
   shown without a fatal error. Existing mailers must continue working.

Live delivery requires an account and was not exercised by the offline suite.

The initial integration intentionally uses synchronous `/send`. Account
provisioning, suppression management, tracking controls, webhooks, and queued
sending can be added separately without changing the transport contract.

No CONTRIBUTING file, PR template, or CI configuration was present in this
checkout or the upstream GitHub community profile when this change was prepared.
Keep the PR focused on this transport; no release version bump is included.
