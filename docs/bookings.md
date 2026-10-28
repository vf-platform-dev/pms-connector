# How bookings behave

API bookings are ordinary RoomZ bookings. They appear on the calendar, the
front desk can edit them, the proforma invoice prices them, and they show in
the night audit and reports. This page covers the rules that matter when you
integrate.

## Availability

A room counts as **taken** for a night when a booking covers it, i.e.
check-in ≤ night < check-out. The departure day is free again.

- **Upgrades.** A guest who booked a Garden View but was moved into a Family
  Room holds a *Family Room*. Availability follows where guests actually are.
- **Not yet assigned.** Bookings still waiting for a room number hold their
  booked room type.
- **Not counted.** Cancelled, void and no-show bookings; rooms released on
  cancellation; and guests who have already checked out.

The same rules feed the lodge's channel manager (Nightsbridge), so the API and
the online travel agents always see the same stock.

## Room allocation

For each room in a booking:

1. If one physical room is free for the **whole** stay, the lowest-numbered
   such room is assigned.
2. If no single room spans the stay but **every night** still has a room of
   that type, the room line is accepted **unassigned**. The lodge places it
   by moving rooms around. You get the warning `rooms_unassigned`, and
   `room_id` is `null`.
3. If **any** night has no room of that type left, the whole booking is
   refused with `409 no_availability`, and nothing is written.

Two API bookings arriving at the same moment can't both take the last room,
because availability is checked and written as one step per property.

## Status

| `status` | Meaning |
|---|---|
| `provisional` | default for API bookings. The lodge confirms it |
| `confirmed` | requires the `bookings:confirm` scope |
| `checked_in`, `checked_out` | set by the front desk |
| `cancelled` | cancelled by you or by the lodge |

## Prices

The booking stores one priced line per room per night, using the same rules as
the PMS reservation screen. A quote made a moment before a booking gives the
same total, unless the lodge changes its rates in between.

- Nights with no rate loaded are priced at **0**, flagged `no_rate`, and the
  lodge sets the price.
- A key linked to an **agent** gets that agent's contract rates
  (`rate_source: agent`).
- `totals.balance` is the accommodation plus any extras, minus payments.
  Payments are taken by the lodge. The API doesn't take payments.

## Changing a booking

| Change | Allowed when |
|---|---|
| guest details, notes, arrival time, voucher | not cancelled, not checked in |
| provisional ↔ confirmed | as above, plus `bookings:confirm` to confirm |
| dates | as above, **and** no payments or charges on the folio yet |

When dates change, assigned rooms are kept if they're still free for the new
dates. If not, they're released for the lodge to reassign (warning
`room_unassigned`). The stay is then repriced.

## Cancelling

Cancellation follows the lodge's policy. By default **the outstanding balance
stays owed** (`keep_balance`), and the response tells you the
`final_balance`. Writing a balance off needs the `bookings:waive` scope. The
rooms are released immediately.

## Your own reference

Send `external_ref` (e.g. your order number) when creating a booking. It's
unique per key, so the same reference can't be booked twice. You can look a
booking up with `GET /bookings?external_ref=…`.
