// RoomZ PMS API - Node.js 18+ example (built-in fetch). Server side only.
//   ROOMZ_API_KEY=rz_xxx node book_room.mjs
const BASE = process.env.ROOMZ_BASE ?? 'https://www.macsoftlabs.co.zw/mukusu/api/v1';
const KEY = process.env.ROOMZ_API_KEY;

class RoomzError extends Error {
  constructor(status, code, message, details, requestId) {
    super(message);
    Object.assign(this, { status, code, details, requestId });
  }
}

async function roomz(method, path, body, idempotencyKey) {
  for (let attempt = 1; ; attempt++) {
    let res;
    try {
      res = await fetch(BASE + path, {
        method,
        headers: {
          Authorization: `Bearer ${KEY}`,
          ...(body ? { 'Content-Type': 'application/json' } : {}),
          ...(idempotencyKey ? { 'Idempotency-Key': idempotencyKey } : {}),
        },
        body: body ? JSON.stringify(body) : undefined,
      });
    } catch (err) {
      if (attempt < 4) { await new Promise(r => setTimeout(r, attempt * 1000)); continue; }
      throw err;
    }
    if ((res.status >= 500 || res.status === 429) && attempt < 4) {
      await new Promise(r => setTimeout(r, attempt * 1000));
      continue; // same Idempotency-Key, so no double booking
    }
    const json = await res.json();
    if (!json.ok) throw new RoomzError(res.status, json.error.code, json.error.message, json.error.details, json.meta?.request_id);
    return json.data;
  }
}

const day = n => new Date(Date.now() + n * 864e5).toISOString().slice(0, 10);
const stay = { property_id: 1, check_in: day(30), check_out: day(32) };
const orderNo = `WEB-${Date.now()}`;

try {
  const q = new URLSearchParams({ ...stay, room_type_id: 1, adults: 2, meal_plan: 'BB' });
  const quote = await roomz('GET', `/rates/quote?${q}`);
  console.log('Price', quote.currency, quote.total, 'available:', quote.available);

  const booking = await roomz('POST', '/bookings', {
    ...stay, meal_plan: 'BB', external_ref: orderNo,
    guest: { firstname: 'Tendai', lastname: 'Moyo', email: 'tendai@example.com' },
    rooms: [{ room_type_id: 1, adults: 2 }],
  }, orderNo);
  console.log('Booked', booking.order_no, booking.status, 'rooms', booking.rooms.map(r => r.room_no ?? 'to be assigned'));
} catch (e) {
  if (e.code === 'no_availability') console.log('Sold out on', e.details.shortfalls[0].short_nights);
  else console.error(e.code, e.message, e.requestId);
}
