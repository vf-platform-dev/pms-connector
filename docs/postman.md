# Postman

The `postman/` folder has a ready collection that creates its own API key and
then runs every endpoint in order.

## 1. Import

In Postman: **Import** → drop in these files:

- `postman/RoomZ-PMS-API.postman_collection.json`
- `postman/Mukusu.postman_environment.json`, or `Template.postman_environment.json`
  for another install

Select the **RoomZ - Mukusu** environment (top-right).

## 2. Fill in the environment

| Variable | Value |
|---|---|
| `baseUrl` | e.g. `https://www.macsoftlabs.co.zw/mukusu/api/v1` |
| `pmsUsername` | a PMS user who can edit Role Permissions (admins can) |
| `pmsPassword` | that user's password. It's a *secret* variable, so it's hidden |
| `propertyId` | optional. It's filled in from **Properties** if left empty |

## 3. Create a key

Open **1. Admin - API keys → Create API key**. Edit the body (name, scopes,
`property_ids`) if you like, then click **Send**.

The response contains `data.api_key`. The collection **saves it into `apiKey`
automatically**, and every other request uses it as a Bearer token. Copy it
somewhere safe if you'll need it outside Postman: it's never shown again.

## 4. Use the API

Run the requests in **2. Catalogue**, **3. Rates** and **4. Bookings**. They
pass values along: the first room type is saved as `roomTypeId`, and a new
booking is saved as `bookingId`. Stay dates default to 30 days from today. Set
`checkIn` and `checkOut` in the environment to choose your own.

> ⚠️ **4. Bookings creates real bookings** in the PMS. Run **Cancel booking**
> afterwards, or point `baseUrl` at a test install.

## 5. Clean up

**5. Clean up → Revoke API key** revokes the key you created in step 3.

## Run it all at once

**Run collection** in Postman, or from the command line:

```bash
npx newman run postman/RoomZ-PMS-API.postman_collection.json \
  -e postman/Mukusu.postman_environment.json \
  --env-var pmsUsername=YOUR_USER --env-var 'pmsPassword=YOUR_PASSWORD'
```

## Troubleshooting

| You see | Fix |
|---|---|
| `https_required` | `baseUrl` must start with `https://` |
| `admin_auth_failed` | wrong username or password, or the user is inactive |
| `not_permitted` | the user's role can't edit Role Permissions |
| `too_many_attempts` | 5 wrong passwords in 15 minutes. Wait, then retry |
| `unauthenticated` on other requests | run **Create API key** first, or paste a key into `apiKey` |
| `unauthenticated` even with a key | the host strips the `Authorization` header. Set the collection auth to *API Key*, with key `X-Api-Key` and value `{{apiKey}}` |
