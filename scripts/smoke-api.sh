#!/usr/bin/env bash
# Smoke test API Laju: health → register-club → login → me → logout.
# Pakai: scripts/smoke-api.sh http://127.0.0.1:8000        (lokal)
#        scripts/smoke-api.sh https://<host-staging>       (staging)
# Membuat 1 klub + 1 user uji (email smoke+<timestamp>@example.com) di database target.
set -euo pipefail

BASE_URL="${1:?Usage: $0 <base-url tanpa /api>}"
BASE_URL="${BASE_URL%/}"
API="$BASE_URL/api/auth"
EMAIL="smoke+$(date +%s)@example.com"
PASSWORD="smoke-Test-12345"

fail() { echo "FAIL: $*" >&2; exit 1; }

# request <method> <url> <expected-status> [data] [token]  → body di $BODY
request() {
  local method="$1" url="$2" expected="$3" data="${4:-}" token="${5:-}"
  local args=(-sS -X "$method" "$url" -H 'Accept: application/json' -H 'Content-Type: application/json' -w '\n%{http_code}')
  [[ -n "$data" ]] && args+=(-d "$data")
  [[ -n "$token" ]] && args+=(-H "Authorization: Bearer $token")
  local out
  out="$(curl "${args[@]}")"
  STATUS="${out##*$'\n'}"
  BODY="${out%$'\n'*}"
  [[ "$STATUS" == "$expected" ]] || fail "$method $url → HTTP $STATUS (harapan $expected): $BODY"
  echo "ok   $method ${url#"$BASE_URL"} → $STATUS"
}

extract_token() { sed -n 's/.*"token":"\([^"]*\)".*/\1/p' <<<"$BODY"; }

request GET "$BASE_URL/up" 200

request POST "$API/register-club" 201 "{\"club_name\":\"Smoke FC\",\"sport_type\":\"uji\",\"name\":\"Smoke Tester\",\"email\":\"$EMAIL\",\"password\":\"$PASSWORD\",\"password_confirmation\":\"$PASSWORD\"}"
grep -q '"role":"admin"' <<<"$BODY" || fail "register-club: user tidak menjadi admin: $BODY"
echo "ok   register-club → role admin"

request POST "$API/login" 200 "{\"email\":\"$EMAIL\",\"password\":\"$PASSWORD\"}"
TOKEN="$(extract_token)"
[[ -n "$TOKEN" ]] || fail "login: token kosong: $BODY"
echo "ok   login → token diterima"

request GET "$API/me" 200 "" "$TOKEN"
grep -q "\"email\":\"$EMAIL\"" <<<"$BODY" || fail "me: user tidak sesuai: $BODY"
grep -q '"role":"admin"' <<<"$BODY" || fail "me: role admin tidak ada: $BODY"
echo "ok   me → user + club_members(admin)"

request GET "$API/me" 401
request POST "$API/logout" 204 "" "$TOKEN"

echo "SEMUA OK ($EMAIL)"
