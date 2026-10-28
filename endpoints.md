# Endpoint reference

Base URL: `https://<host>/<install>/api/v1`. All examples are real responses.
They are shortened only where marked `…`.

- [Status & ping](#status--ping)
- [Meal plans](#get-meal-plans)
- [Properties & room types](#get-properties)
- [Availability](#get-availability)
- [Rate quotes](#rate-quotes)
- [Create booking](#post-bookings)
- [Read booking](#get-bookingsid)
- [Change booking](#patch-bookingsid)
- [Cancel booking](#post-bookingsidcancel)
- [Key management](#key-management)

---

## Status & ping

### `GET /`
No key needed. Confirms the API is up and connected to its database.

```json
{"ok": true, "data": {"service": "RoomZ PMS API", "version": "v1", "status": "running", "database": "connected"}}
```

### `GET /ping`
Any valid key. Shows which key you're using and what it may do.

```json
{"ok": true, "data": {
  "client": "Lodge website",
  "scopes": ["availability:read", "rates:read", "bookings:read", "bookings:write", "bookings:cancel"],
  "property_ids": [1], "agent_id": null, "server_time": "2026-10-28T12:01:27+02:00"}}
```

---

## `GET /meal-plans`
`availability:read`

```json
{"ok": true, "data": [
  {"code": "BB", "title": "Bed + Breakfast"}, {"code": "HB", "title": "Half Board"},
  {"code": "FB", "title": "Full Board"},     {"code": "BO", "title": "Bed Only"}]}
```

---

## `GET /properties`
`availability:read`. Lists the properties the key may access.

## `GET /properties/{id}`

```json
{"ok": true, "data": {"id": 1, "name": "Mukusu Lodge", "code": "MK", "email": "…", "phone": "…",
  "address": "…", "city": "Victoria Falls", "currency_code": "USD", "charge_basis_mode": "PER_PERSON"}}
```

`charge_basis_mode` is `PER_PERSON`, `PER_ROOM` or `BOTH`. Only for `BOTH`
does the `charge_basis` you send on quotes and bookings make a difference.

## `GET /properties/{id}/room-types`

```json
{"ok": true, "data": [
  {"id": 2, "title": "Family Room", "code": "FR", "description": null,
   "base_occupancy": 2, "max_occupancy": 4, "max_children": 3, "extra_beds": 0, "room_count": 2},
  {"id": 1, "title": "Garden View", "code": "GV", "description": null,
   "base_occupancy": 2, "max_occupancy": 3, "max_children": 2, "extra_beds": 0, "room_count": 3}]}
```

---

## `GET /availability`
`availability:read`

| Query | Required | |
|---|---|---|
| `property_id` | yes | |
| `check_in`, `check_out` | yes | at most 120 nights |
| `room_type_id` | no | one room type only |

```json
{"ok": true, "data": {"property_id": 1, "check_in": "2026-12-10", "check_out": "2026-12-13",
  "room_types": [{
    "room_type_id": 1, "room_type": "Garden View", "total_rooms": 3,
    "available_for_stay": 2,
    "nights": [{"date": "2026-12-10", "available": 3},
               {"date": "2026-12-11", "available": 2},
               {"date": "2026-12-12", "available": 3}]}]}}
```

`available_for_stay` is the lowest nightly figure: the number of rooms of that
type you could book for the whole stay. See [bookings](docs/bookings.md#availability)
for how rooms are counted.

---

## Rate quotes
`rates:read`. A quote reserves nothing.

### `GET /rates/quote` - one room
`property_id`, `room_type_id`, `check_in`, `check_out`, `adults` (default 2),
`children` (default 0), `meal_plan` (default `BB`), and optionally
`charge_basis` (`PER_PERSON` or `PER_ROOM`).

### `POST /rates/quote` - several rooms
```json
{"property_id": 1, "check_in": "2026-12-10", "check_out": "2026-12-12", "meal_plan": "BB",
 "rooms": [{"room_type_id": 1, "adults": 2, "children": 1}, {"room_type_id": 2, "adults": 2}]}
```

Response (one room, 2 adults + 1 child, 2 nights):
```json
{"ok": true, "data": {
  "property_id": 1, "check_in": "2026-12-10", "check_out": "2026-12-12", "meal_plan": "BB",
  "available": true, "currency": "USD", "nights": 2, "total": 750.0,
  "rooms": [{
    "room_type_id": 1, "adults": 2, "children": 1, "total": 750.0,
    "nights": [
      {"date": "2026-12-10", "amount": 375.0, "adult_total": 300.0, "child_total": 75.0,
       "applied_rate_type": "sharing", "rate_plan_id": 1, "rate_plan": "Rack BB",
       "rate_source": "public", "charge_basis": "PER_PERSON"},
      {"date": "2026-12-11", "amount": 375.0, "…": "…"}]}],
  "warnings": []}}
```

- `available` tells you whether the rooms are free right now. When it's
  `false`, `shortfalls` lists the full nights.
- `rate_source` is one of:
  - `public`: the rack rate;
  - `agent`: the contract rate of the agent linked to your key;
  - `agent_derived`: the contract rate plus a meal supplement;
  - `public_fallback`: the agent had no contract rate for that night, so the
    rack rate was used.
- `warnings`:
  - `no_rate`: no rate is loaded for that night, so it's priced at 0 and the
    lodge confirms the price;
  - `agent_rate_fallback`;
  - `occupancy_exceeded`: more guests than the room type normally sleeps.

---

## `POST /bookings`
`bookings:write`. Send an `Idempotency-Key` header (see [errors](docs/errors.md#idempotency)).

```json
{
  "property_id": 1,
  "check_in": "2026-12-10",
  "check_out": "2026-12-12",
  "meal_plan": "BB",
  "status": "provisional",
  "external_ref": "WEB-7781",
  "expected_arrival_time": "14:30",
  "notes": "Honeymoon couple",
  "voucher_no": "",
  "charge_basis": "PER_PERSON",
  "guest": {"firstname": "Tendai", "lastname": "Moyo", "email": "tendai@example.com", "phone": "+263771000000"},
  "rooms": [
    {"room_type_id": 1, "adults": 2, "children": 0},
    {"room_type_id": 1, "adults": 1, "guest": {"firstname": "Rudo", "lastname": "Moyo"}}
  ]
}
```

| Field | | |
|---|---|---|
| `property_id`, `check_in`, `check_out` | required | at most 60 nights, within 730 days |
| `guest.firstname`, `guest.lastname` | required | the lead guest. `email` and `phone` are optional |
| `rooms[]` | required | 1–10 rooms. Each needs `room_type_id` and `adults`. `children` is optional. A room can have its own `guest` |
| `meal_plan` | optional | default `BB` |
| `status` | optional | `provisional` (default) or `confirmed`, which needs `bookings:confirm` |
| `external_ref` | optional | your own reference, unique per key |
| `expected_arrival_time` | optional | `HH:MM` |
| `notes`, `voucher_no`, `charge_basis` | optional | |

**201 Created**
```json
{"ok": true, "data": {
  "id": 8, "order_no": "BK-20261028120515-921", "external_ref": "WEB-7781",
  "property_id": 1, "status": "provisional",
  "check_in": "2026-12-10", "check_out": "2026-12-12", "nights": 2, "meal_plan": "BB",
  "expected_arrival_time": "14:30", "voucher_no": null, "notes": "Honeymoon couple", "source": "website",
  "guest": {"id": 7, "firstname": "Tendai", "lastname": "Moyo", "email": "tendai@example.com", "phone": "+263771000000"},
  "rooms": [{"rooming_id": 9, "room_type_id": 1, "room_type": "Garden View", "room_id": 1, "room_no": "1",
             "adults": 2, "children": 0, "check_in": "2026-12-10", "check_out": "2026-12-12",
             "checked_in": false, "checked_out": false, "released": false}],
  "currency": "USD",
  "totals": {"accommodation": 600.0, "charges": 600.0, "payments": 0.0, "balance": 600.0},
  "created_at": "2026-10-28 12:05:15",
  "warnings": []}}
```

`room_id` and `room_no` are `null` when the lodge still has to assign a room.
In that case `warnings` contains `rooms_unassigned`. See
[bookings](docs/bookings.md#room-allocation).

**409** `no_availability` (nothing was booked):
```json
{"ok": false, "error": {"code": "no_availability", "message": "Not enough rooms available for the requested dates.",
  "details": {"shortfalls": [{"room_type_id": 1, "requested": 1, "min_free": 0, "short_nights": ["2026-12-11"]}]}}}
```

---

## `GET /bookings/{id}`
`bookings:read`. Returns the same shape as the create response. A key sees only
the bookings it created, unless it has `bookings:read_all`. Any other booking
returns `404`.

## `GET /bookings?external_ref=WEB-7781`
Finds your booking by your own reference. Returns a list with zero or one
bookings.

---

## `PATCH /bookings/{id}`
`bookings:write`, and only for bookings this key created. Send only the fields
you're changing:

```json
{"notes": "Late arrival", "expected_arrival_time": "21:00",
 "guest": {"firstname": "Tendai", "lastname": "Moyo", "email": "new@example.com", "phone": "+263771000001"},
 "status": "confirmed",
 "check_in": "2026-12-11", "check_out": "2026-12-14"}
```

- `status` takes `confirmed` (needs `bookings:confirm`) or `provisional`. To
  cancel, use the cancel endpoint.
- Date changes re-check availability and reprice the stay. They are refused
  (`409 folio_activity`) once the booking has payments or charges.
- Any change is refused once the guest has checked in (`409 booking_in_house`).
- Room types and room counts can't be changed through the API. Cancel and
  rebook instead.

---

## `POST /bookings/{id}/cancel`
`bookings:cancel`, and only for bookings this key created.

```json
{"reason": "Guest changed plans", "policy_action": "keep_balance"}
```

| `policy_action` | Effect | Scope |
|---|---|---|
| `keep_balance` (default) | the outstanding balance stays owed (lodge policy) | `bookings:cancel` |
| `waive_all` | the balance is written off | + `bookings:waive` |
| `waive_and_charge_policy` | the balance is written off and `policy_charge` is charged instead | + `bookings:waive` |

The response is the booking (`"status": "cancelled"`) plus:
```json
"cancellation": {"policy_action": "keep_balance", "original_balance": 600.0, "adjustment": 0.0,
                 "policy_charge": 0.0, "final_balance": 600.0,
                 "warning": "Balance of 600.00 was NOT waived and remains owed on this booking."}
```

The rooms are released immediately and appear in `/availability` again.

---

## Key management
PMS admin login over HTTP Basic Auth, HTTPS only. See
[authentication](docs/authentication.md#over-the-api-postman-scripts).

| | |
|---|---|
| `GET /admin/api-keys` | list keys, never the secret |
| `POST /admin/api-keys` | create a key. The full key is in `data.api_key` **once** |
| `POST /admin/api-keys/{id}/revoke` | revoke immediately |
