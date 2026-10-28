#!/usr/bin/env bash
# RoomZ PMS API - end-to-end walkthrough with curl.
# Usage:  BASE=https://www.macsoftlabs.co.zw/mukusu/api/v1 KEY=rz_xxx bash curl.sh
set -euo pipefail
: "${BASE:?set BASE to the API address}"
: "${KEY:?set KEY to your API key}"
AUTH=(-H "Authorization: Bearer $KEY")
JSON=(-H "Content-Type: application/json")
IN=$(date -d '+30 days' +%F 2>/dev/null || date -v+30d +%F)
OUT=$(date -d '+32 days' +%F 2>/dev/null || date -v+32d +%F)

echo "== Key";            curl -s "$BASE/ping" "${AUTH[@]}"; echo
echo "== Properties";     curl -s "$BASE/properties" "${AUTH[@]}"; echo
echo "== Room types";     curl -s "$BASE/properties/1/room-types" "${AUTH[@]}"; echo
echo "== Availability";   curl -s "$BASE/availability?property_id=1&check_in=$IN&check_out=$OUT" "${AUTH[@]}"; echo
echo "== Quote";          curl -s "$BASE/rates/quote?property_id=1&room_type_id=1&check_in=$IN&check_out=$OUT&adults=2" "${AUTH[@]}"; echo

echo "== Book"
REF="CURL-$(date +%s)"
curl -s -X POST "$BASE/bookings" "${AUTH[@]}" "${JSON[@]}" -H "Idempotency-Key: $REF" -d @- <<EOF
{"property_id": 1, "check_in": "$IN", "check_out": "$OUT", "meal_plan": "BB", "external_ref": "$REF",
 "guest": {"firstname": "Test", "lastname": "Guest", "email": "test@example.com"},
 "rooms": [{"room_type_id": 1, "adults": 2}]}
EOF
echo

echo "== Find it again";  curl -s "$BASE/bookings?external_ref=$REF" "${AUTH[@]}"; echo
# Cancel:  curl -s -X POST "$BASE/bookings/<id>/cancel" "${AUTH[@]}" "${JSON[@]}" -d '{"reason":"test"}'
