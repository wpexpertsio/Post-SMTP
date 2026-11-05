# Modern mail pipeline

Connection-based delivery for the Post SMTP connections UI. Classic sites keep using `PostmanWpMail`.

## When it runs

`CohortResolver::usesRevampMailPipeline()` is true (connections mode; filter `post_smtp_use_revamp_mail_pipeline`).

## Layout

| File | Role |
|------|------|
| `Register.php` | Bootstraps `WpMailHandler` + `Settings\OptionsReconciler` |
| `WpMailHandler.php` | `pre_wp_mail` entry |
| `Delivery/Orchestrator.php` | Primary attempt, then fallbacks |
| `Delivery/Plan.php` | Primary = `postman_connections[0]`, fallbacks from `ConnectionService` |
| `Envelope/` | Normalized `wp_mail` payload for providers |
| `EmailLog/Writer.php` | Persists log rows (primary update vs fallback insert) |
| `EmailLog/Events.php` | Builds Postman log objects + `post_smtp_before_fallback_delivery` |
| `Settings/OptionsReconciler.php` | Syncs `postman_options` from connection 0 (UI only) |

## Public API

- **`PostSMTP\Mail\MailPipeline::send()`** — same orchestrator as `wp_mail`.
- **`PostSMTP\Mail\Classic\WpMailAdapter`** — classic programmatic path.

## Data

- **Send:** `postman_connections` via `ConnectionRepositoryFactory`.
- **`postman_options`:** reconciled for log UI; not used to pick transport on send.

## Hooks (unchanged names)

- `post_smtp_revamp_before_provider_send` / `post_smtp_revamp_after_provider_send`
- `post_smtp_should_try_fallback`
- `post_smtp_before_fallback_delivery`

## Related

- Providers: `kernel/Providers/Modern/*`
- Classic runtime patch: `src/Connections/ConnectionLegacySendBridge.php`
