# Changelog

## v1.0 - 2026-10-28
First release of the RoomZ PMS Connector API.

- Catalogue: `/properties`, `/properties/{id}/room-types`, `/meal-plans`
- `/availability`: free rooms per room type per night, using the same counting as the channel manager
- `/rates/quote`: GET for one room, POST for several; agent contract rates through agent-linked keys
- Bookings: create, read, find by `external_ref`, change (details, status, dates), cancel
- Idempotency keys on all writes; per-key rate limits; request log
- Key management: PMS *Configuration → API Keys* page and `/admin/api-keys` (PMS admin login, HTTPS only)
