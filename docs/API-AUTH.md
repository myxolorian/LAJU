# API Auth (Fase 0)

Base: `/api`. Selalu kirim `Accept: application/json`. Auth memakai token Sanctum: `Authorization: Bearer <token>`.
Token kedaluwarsa setelah `SANCTUM_TOKEN_EXPIRATION` menit (default 43200 = 30 hari).

| Method | Path | Auth | Fungsi |
|---|---|---|---|
| POST | `/auth/register-club` | – | Buat klub baru + akun + keanggotaan `admin` dalam **satu DB transaction** |
| POST | `/auth/login` | – | Email + password → token |
| GET | `/auth/me` | Bearer | User + seluruh `club_members` (dengan data klub) |
| POST | `/auth/logout` | Bearer | Cabut token saat ini (204) |

`register-club` dan `login` dibatasi 10 request/menit per IP+email (`throttle:auth`).

## POST /auth/register-club

```json
{
  "club_name": "Garuda Muda FC",
  "sport_type": "sepak bola",
  "name": "Budi Santoso",
  "email": "budi@example.com",
  "phone": "081234567890",
  "password": "min-8-karakter",
  "password_confirmation": "min-8-karakter",
  "device_name": "web"
}
```

`phone` dan `device_name` opsional. Email harus belum terdaftar (422 bila sudah; akun yang sudah ada
membuat klub tambahan lewat flow terautentikasi — belum termasuk Fase 0).

201:

```json
{
  "token": "1|...",
  "user": {
    "id": 1, "name": "Budi Santoso", "email": "budi@example.com", "phone": null,
    "club_members": [{
      "id": 1, "club_id": 1, "role": "admin", "status": "aktif", "joined_at": "2026-09-20T09:38:35.000000Z",
      "club": { "id": 1, "name": "Garuda Muda FC", "sport_type": "sepak bola", "logo_url": null,
                "subscription_plan": "free", "subscription_status": "active" }
    }]
  }
}
```

## POST /auth/login

Body `{ "email", "password", "device_name"? }` → 200 `{ "token", "user": {…sama seperti di atas…} }`.
Kredensial salah → 422 dengan `errors.email`.

## GET /auth/me

200 `{ "user": {…} }`. FE: `club_members.length === 1` → langsung masuk klub itu; lebih dari 1 → tampilkan club switcher.
