# Authentication & API keys

## API keys

A key looks like `rz_1a2b3c4d_EXAMPLE0000000000000000000000000000000000`:

- `rz_` is a fixed prefix;
- `1a2b3c4d` is the public key ID, shown in key lists;
- the rest is the secret.

The PMS stores only a SHA-256 hash of the key, so **a lost key can't be
recovered**. Revoke it and create a new one.

Send it as `Authorization: Bearer <key>`, or as `X-Api-Key: <key>`.

## Scopes

Each key carries a list of scopes:

| Scope | Allows |
|---|---|
| `availability:read` | properties, room types, meal plans, availability |
| `rates:read` | rate quotes |
| `bookings:read` | read bookings **this key** created |
| `bookings:read_all` | read **any** booking at the key's properties |
| `bookings:write` | create bookings, change bookings this key created |
| `bookings:confirm` | create or mark bookings as **Confirmed**. Without it, bookings are **Provisional** |
| `bookings:cancel` | cancel bookings this key created |
| `bookings:waive` | waive the balance when cancelling |

Typical sets:

| Use | Scopes |
|---|---|
| Availability widget in a web page | `availability:read`, `rates:read` |
| Website booking engine (server side) | the above + `bookings:read`, `bookings:write`, `bookings:cancel` |
| Trusted tour operator | the above + `bookings:confirm`, with an agent attached |

## Other limits on a key

- **Properties.** A key can be limited to specific properties. It gets `403`
  on any other property, and `404` on bookings belonging to them.
- **Agent.** If a key is linked to an agent, every quote and booking uses that
  agent's contract rates and is stamped with the agent. Callers can't choose
  an agent themselves, so a public key can never reach contract rates.
- **IP allowlist.** Requests from other IPs get `403 ip_not_allowed`.
- **CORS origins.** These let a browser on that origin call the API.
  **Only use this for read-only keys.** Anything that can book must stay on
  your server.
- **Rate limit.** Requests per minute. The default is 120. See
  [errors](errors.md).

## Managing keys

### In the PMS
*Configuration → API Keys* (`/modules/settings/api_keys.php`). Needs the
*Role Permissions → edit* right.

### Over the API (Postman, scripts)
Key management uses a **PMS user login**, not an API key: HTTP Basic Auth with
the username and password of a user who may edit Role Permissions. Admins and
super admins always qualify. These calls work **over HTTPS only**. After five
wrong passwords from one IP within 15 minutes, that IP is locked out for the
rest of the window.

```bash
# Create
curl -u 'admin:password' -X POST "$BASE/admin/api-keys" -H 'Content-Type: application/json' -d '{
  "name": "Lodge website",
  "scopes": ["availability:read", "rates:read", "bookings:read", "bookings:write", "bookings:cancel"],
  "property_ids": [1],
  "booking_source": "website",
  "notes": "Built by XYZ Web, contact jane@xyz.co.zw"
}'
```
```json
{"ok": true, "data": {
  "id": 7, "name": "Lodge website", "key_prefix": "rz_1a2b3c4d_…",
  "scopes": ["availability:read","rates:read","bookings:read","bookings:write","bookings:cancel"],
  "property_ids": [1], "agent_id": null, "booking_source": "website", "active": true,
  "api_key": "rz_1a2b3c4d_EXAMPLE0000000000000000000000000000000000",
  "warning": "This is the only time the full key is shown. Store it securely."
}}
```

```bash
# List (never shows the secret)
curl -u 'admin:password' "$BASE/admin/api-keys"

# Revoke
curl -u 'admin:password' -X POST "$BASE/admin/api-keys/7/revoke"
```

Optional fields on create: `property_ids` (empty means all), `agent_id`,
`booking_source`, `allowed_ips`, `allowed_origins`, `rate_limit_per_minute`,
`notes`. The wildcard `*` can't be granted over the API. List the scopes you
need.

## Keeping keys safe

- Keep keys in server-side config or a secrets manager. Never commit them to
  Git.
- Use one key per integration, so you can revoke one without breaking the
  others.
- If a key leaks, revoke it straight away. It stops working on the next
  request.
