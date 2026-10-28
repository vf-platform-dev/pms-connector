# pms-connector

The API client for Macdonald's Roomez property management system: bookings, transfers and activities for Batonka and Pioneers first. Document every endpoint in `endpoints.md` (URL, fields, example response).

**Owner:** Macdonald · Part of VenueLink (working name). Team brief and decisions: [vf-platform-dev/company](https://github.com/vf-platform-dev/company).

---

## Roomez API v1: what's available now

Each Roomez install exposes a JSON API at `https://<host>/<install>/api/v1`.
With one API key you can:

- list properties, room types and meal plans
- check live availability per room type per night
- get rate quotes, including agent contract rates
- create, read, change and cancel **room bookings**. They land in the PMS
  exactly like front-desk bookings and stay managed by the lodge

**Not yet:** transfers and activities. These come next, following the MVP
order *booking system → lodge services → activities*.

> This repo holds the **API documentation and client examples only**. The
> Roomez server code is not here, pending
> [open question #8](https://github.com/vf-platform-dev/company/blob/main/open-questions.md)
> on Roomez IP.

## Quick start

1. **Check the install is up.** Open its base URL. You should see
   `"status": "running"`.
2. **Get a key.** Macdonald (or any Roomez admin) creates one in the PMS under
   *Configuration → API Keys*, or from Postman ([guide](docs/postman.md)).
   Keys look like `rz_1a2b3c4d_…`. Keep them in environment variables, never
   in Git.
3. **Call the API.**
   ```bash
   curl "$ROOMZ_BASE/availability?property_id=1&check_in=2026-12-10&check_out=2026-12-13" \
        -H "Authorization: Bearer $ROOMZ_API_KEY"
   ```

## Documentation

| | |
|---|---|
| [endpoints.md](endpoints.md) | **Every endpoint**: URL, fields, example responses |
| [Getting started](docs/getting-started.md) | Request/response format, your first booking |
| [Authentication & API keys](docs/authentication.md) | Keys, scopes, property limits, agent keys, managing keys |
| [How bookings behave](docs/bookings.md) | Availability, room allocation, changes, cancellation policy |
| [Errors, idempotency & limits](docs/errors.md) | Error codes, safe retries, rate limits |
| [Postman](docs/postman.md) | Import the collection, create a key, run everything |
| [openapi/openapi.yaml](openapi/openapi.yaml) | OpenAPI 3.0 spec for code generators and Swagger/Redoc. Open `index.html` locally for a browsable view |
| [CHANGELOG.md](CHANGELOG.md) | API versions |

## Endpoints at a glance

| Method | Path | Needs |
|---|---|---|
| GET | `/` | nothing (status check) |
| GET | `/ping` | any key |
| GET | `/meal-plans`, `/properties`, `/properties/{id}`, `/properties/{id}/room-types` | `availability:read` |
| GET | `/availability` | `availability:read` |
| GET / POST | `/rates/quote` | `rates:read` |
| POST | `/bookings` | `bookings:write` |
| GET | `/bookings/{id}`, `/bookings?external_ref=` | `bookings:read` |
| PATCH | `/bookings/{id}` | `bookings:write` |
| POST | `/bookings/{id}/cancel` | `bookings:cancel` |
| GET / POST | `/admin/api-keys` | Roomez admin login (Basic Auth, HTTPS) |
| POST | `/admin/api-keys/{id}/revoke` | Roomez admin login (Basic Auth, HTTPS) |

## Repo layout

```
endpoints.md   endpoint reference (the repo's required doc)
docs/          guides
openapi/       OpenAPI 3.0 specification
postman/       Postman collection + environment templates (no credentials)
examples/      curl, PHP and Node.js client examples
index.html     browsable reference (Redoc) - open locally
```

## Notes for builders (JJ, Christopher)

- **Payments.** The API takes no payments. Bookings are created with a
  balance, and the lodge collects. This matches the decision that VenueLink
  does not hold client funds.
- **Status.** New bookings are *provisional* unless the key has
  `bookings:confirm`.
- **Vendor-neutral model.** Map these responses into the VenueLink model at
  your layer rather than spreading Roomez field names through the platform.
  This is what open question #13 asks for.
- Every response has a `meta.request_id`. Quote it when reporting a problem.
