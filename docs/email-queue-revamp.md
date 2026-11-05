# Email Queue revamp (Advanced Delivery)

Revamp Settings → **Email Queue** and **Email Logs (Auto Retry)** read and write the legacy option `ps_aedl_option`. Pro runtime queue, batch cron, quota, and flush-on-disable integrate with the **revamp mail pipeline** (`pre_wp_mail` / `WpMailHandler`) while legacy sites on `PostmanWpMail` keep existing Pro hooks.

## User-facing behavior

| Audience | Email Queue tab |
|----------|-----------------|
| **Free** | Four Pro feature previews (Advanced Delivery, Batch, Quota, Auto Retry) with magnifier lightbox and upgrade CTA. POST saves are blocked (`panel_locked`). |
| **Pro** | Full form: Advanced Delivery, batch size/interval, quota toggles and values, Auto Retry (same fields as Email Logs). |

**Advanced Delivery off:** REST save fires `post_smtp_aedl_advance_delivery_disabled`. Pro drains pending `In Queue` rows in a time-boxed loop (immediate send attempt).

**Run modes (Settings → Security):**

| Mode | Revamp pipeline |
|------|-----------------|
| **No action (`ignore`)** | `wp_mail` returns success; no log, no send, no queue. |
| **Log only** | Log row only; no provider send, no queue. |
| **Production** | Eligible for Advanced Delivery queue when Pro + extension + `enable_advance_delivery`. |

## Option keys and defaults

Storage: **`ps_aedl_option`** (see `PostSMTP\Settings\AedlOptions`).

| Key | New-site default (missing keys only) |
|-----|--------------------------------------|
| `enable_advance_delivery` | `1` |
| `emails_per_batch` | `10` |
| `batch_duration` | `5` (seconds) |
| `enable_quota` | `0` |
| `advanced_quota_settings` | `0` |
| `quota_value` | `500` |
| `quota_duration` | `d` (`h\|d\|w\|m`) |
| `enable_auto_retry` | `1` |
| `times_to_retry` | `3` |

GET never writes defaults to the database. Invalid Pro POST returns `WP_Error` without overwriting stored values.

## Revamp queue flow

1. `WpMailHandler` (`pre_wp_mail`) runs for revamp cohort sites.
2. Batch/cron resend sets `$GLOBALS['post_smtp_aedl_batch_send']` or filter `post_smtp_revamp_force_send` → **send now** (no re-queue).
3. Filter `post_smtp_revamp_should_queue_mail` (Pro: `RevampAedlRuntime`) when Advanced Delivery is enabled → persist log with status **`In Queue`** via `Writer::persistQueued()` / `Events::persistQueued()`; **Orchestrator is not called**. Within each batch window (`batch_duration`), up to **`emails_per_batch`** messages send immediately; additional messages in that window are queued.
4. Batch/cron resend **updates** the same log row to success/failed (via `post_smtp_aedl_batch_log_id` + `Writer::persistAttempt`).
5. React Email Logs polls every 5s while any visible row is **Queued**, so status flips to Success without a manual refresh.

Default queued status string: filter `post_smtp_revamp_queue_log_status` (default `'In Queue'`).

## Batch, quota, retry (Pro extension)

### Revamp mail pipeline

When `CohortResolver::usesRevampMailPipeline()` is true, the extension **does not load** legacy runtime files (`ps-advance-delivery.php`, `ps-quota.php`, `ps-batch.php`). Instead, Pro registers:

| Class | Role |
|-------|------|
| `PostSMTP\Pro\Bootstrap\RevampAedlRuntime` | Queue filter, flush on disable, WP-Cron batch schedule |
| `PostSMTP\Pro\Aedl\BatchService` | Queued log SQL + resend via `wp_mail` + `post_smtp_aedl_batch_send` |
| `PostSMTP\Pro\Aedl\QuotaService` | Period transient + counter; increments on successful revamp sends |

Cron hook: `post_smtp_batch_cron_hook`, schedule key `batch_interval`.

### Legacy PostmanWpMail sites

Legacy runtime uses `post_smtp_send_email` / `wp_mail` filters in `ps-advance-delivery.php`, `ps-quota.php`, and `PS_AEDL_Batch` + `PS_AEDL_Batch_Processor`.

Auto retry: failed batch sends increment log meta `retried` up to `times_to_retry` (both cohorts). Retries run in batch cron after a failed send attempt.

## Extension points

| Hook / filter | Purpose |
|---------------|---------|
| `post_smtp_revamp_should_queue_mail` | Return true to queue instead of sending. |
| `post_smtp_revamp_queue_log_status` | Log status column when queued (default `In Queue`). |
| `post_smtp_revamp_force_send` | Force immediate send (batch/flush). |
| `post_smtp_aedl_advance_delivery_disabled` | Flush queue (Pro listener). |

## Reports

Dashboard quota meter (Pro Report & Tracking) uses `enable_quota` and `advanced_quota_settings` from the same option; meter hidden when either toggle is off.

## QA checklist (manual)

- [ ] Free: GET previews; POST email-queue rejected when locked.
- [ ] Pro: save batch/quota/retry; invalid batch `0` rejected.
- [ ] Production + Advanced Delivery on: new mail → `In Queue`, no immediate provider send.
- [ ] Log only / ignore: no queue row / no send per mode.
- [ ] Cron sends ≤ `emails_per_batch` per interval.
- [ ] Quota exceeded: batch stops; extra mail stays queued.
- [ ] Disable Advanced Delivery: flush sends pending queue.
- [ ] Auto retry values match after save on Email Queue vs Email Logs tabs.
