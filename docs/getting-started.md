# Getting started

## Base URL

Every RoomZ install has its own API address:

```
https://<host>/<install>/api/v1
```

For example `https://www.macsoftlabs.co.zw/mukusu/api/v1`.

Open the base URL in a browser to check it is up:

```json
{"ok": true, "data": {"service": "RoomZ PMS API", "version": "v1", "status": "running", "database": "connected"}}
```

> If the host has no URL rewriting, use `.../api/v1/index.php?route=/properties`
> in place of `.../api/v1/properties`. Everything else is the same.

## Authentication

Send your key on every request:

```
Authorization: Bearer rz_1a2b3c4d_EXAMPLE0000000000000000000000000000000000
```

If something between you and the server strips the `Authorization` header (some
shared hosts do), send `X-Api-Key: rz_...` instead.

See [Authentication & API keys](authentication.md).

## Requests and responses

- Request bodies are JSON (`Content-Type: application/json`).
- Dates are `YYYY-MM-DD` in the lodge's local time (Africa/Harare).
  `check_out` is the departure day, so `2026-12-10` → `2026-12-13` is
  **3 nights**.
- Money is a number in the property's currency, which is returned as
  `currency`.

Every response has the same shape:

```json
{ "ok": true,  "data": { ... }, "meta": { "request_id": "req_6a6e6ac69b763ba1" } }
```
```json
{ "ok": false,
  "error": { "code": "no_availability", "message": "Not enough rooms available for the requested dates.",
             "details": { "shortfalls": [ { "room_type_id": 1, "requested": 1, "min_free": 0, "short_nights": ["2026-12-11"] } ] } },
  "meta": { "request_id": "req_..." } }
```

Check `ok` first. Then use `error.code` to decide what to do. `message` is
meant for humans and may change.

## Your first booking, step by step

```bash
BASE=https://www.macsoftlabs.co.zw/mukusu/api/v1
KEY=rz_xxxxxxxx_...

# 1. Which property and room types?
curl -s "$BASE/properties" -H "Authorization: Bearer $KEY"
curl -s "$BASE/properties/1/room-types" -H "Authorization: Bearer $KEY"

# 2. Is there space?
curl -s "$BASE/availability?property_id=1&check_in=2026-12-10&check_out=2026-12-13" -H "Authorization: Bearer $KEY"

# 3. What will it cost?
curl -s "$BASE/rates/quote?property_id=1&room_type_id=1&check_in=2026-12-10&check_out=2026-12-13&adults=2&meal_plan=BB" \
     -H "Authorization: Bearer $KEY"

# 4. Book it (the Idempotency-Key makes a retry safe)
curl -s -X POST "$BASE/bookings" -H "Authorization: Bearer $KEY" -H "Content-Type: application/json" \
     -H "Idempotency-Key: website-order-7781" \
     -d '{
       "property_id": 1, "check_in": "2026-12-10", "check_out": "2026-12-13", "meal_plan": "BB",
       "external_ref": "WEB-7781",
       "guest": {"firstname": "Tendai", "lastname": "Moyo", "email": "tendai@example.com", "phone": "+263771000000"},
       "rooms": [{"room_type_id": 1, "adults": 2}]
     }'
```

The response is the full booking, including its `id`, `order_no`, the assigned
room numbers and the total. Keep the `id`, or use your own `external_ref`, to
read, change or cancel it later.

## Next

- [Endpoint reference](../endpoints.md)
- [How bookings behave](bookings.md)
- [Errors, retries and limits](errors.md)
