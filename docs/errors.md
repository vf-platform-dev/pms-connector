# Errors, idempotency & limits

## Error format

```json
{"ok": false,
 "error": {"code": "validation_failed", "message": "check_out must be after check_in.", "details": {"field": "check_out"}},
 "meta": {"request_id": "req_ec6e2f69b458d97b"}}
```

Branch on `error.code`. Show `message` to humans. Log `request_id`: the lodge
can find the exact request with it.

| HTTP | `code` | Meaning / what to do |
|---|---|---|
| 400 | `invalid_json` | Body isn't a JSON object |
| 401 | `unauthenticated` | Missing or wrong key |
| 401 | `key_revoked` | Key was revoked. Ask for a new one |
| 401 | `admin_auth_required`, `admin_auth_failed` | Key management: send a valid PMS login |
| 403 | `insufficient_scope` | Key lacks the scope in `details.required_scope` |
| 403 | `property_not_allowed` | Key isn't allowed on that property |
| 403 | `ip_not_allowed` | Calling from an IP that isn't on the key's allowlist |
| 403 | `https_required` | Key management over plain http |
| 403 | `not_permitted` | The PMS user can't manage keys |
| 404 | `not_found` | No such record, or one your key can't see |
| 404 | `route_not_found` | Wrong URL |
| 405 | `method_not_allowed` | Wrong HTTP method. See the `Allow` header |
| 409 | `no_availability` | Rooms are gone. `details.shortfalls` lists the nights |
| 409 | `duplicate_external_ref` | You already booked this `external_ref`. `details.booking_id` |
| 409 | `booking_cancelled` | Booking is already cancelled |
| 409 | `booking_in_house` | Guest has checked in. Contact the lodge |
| 409 | `folio_activity` | Payments or charges exist. Date changes must go through the lodge |
| 409 | `request_in_progress` | Same Idempotency-Key is still running. Retry shortly |
| 422 | `validation_failed` | Bad input. `details.field` names the field |
| 422 | `idempotency_key_reused` | Same Idempotency-Key with a different body |
| 429 | `rate_limited`, `too_many_attempts` | Slow down. See `Retry-After` |
| 503 | `busy` | Another booking is being written for this property. Retry in a second |
| 500 | `internal_error` | Our fault. Retry later and quote `request_id` |

## Idempotency

Network calls can time out *after* the server has done the work. To make
retries safe, send a unique `Idempotency-Key` header on every `POST` and
`PATCH`:

```
Idempotency-Key: website-order-7781
```

- **Same key, same body:** you get the original response again (header
  `Idempotent-Replayed: true`). No second booking is made.
- **Same key, different body:** `422 idempotency_key_reused`.
- Keys are remembered for 48 hours.
- `5xx` responses aren't stored, so a retry runs the request again.

Your order number makes a good key. Use a fresh key for each separate request.

## Rate limits

Each key has a per-minute limit (default 120). Every response carries:

```
X-RateLimit-Limit: 120
X-RateLimit-Remaining: 117
```

Going over returns `429` with `Retry-After: 60`.

## Retrying

| Response | Retry? |
|---|---|
| network error, timeout, `500`, `503` | yes, with the **same** Idempotency-Key, backing off 1 s, 2 s, 4 s… |
| `429` | yes, after `Retry-After` |
| `409 request_in_progress` | yes, after a second |
| other `4xx` | no. Fix the request |
