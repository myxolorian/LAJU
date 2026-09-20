# Deploy staging — Laravel Cloud

Panduan T5. Langkah 1–5 dilakukan di dashboard Laravel Cloud oleh pemilik akun (butuh login + izin GitHub).
**Jangan commit secret apa pun ke repo** — semua nilai di bawah diisi lewat Environment → Variables di dashboard.

## 1. Buat aplikasi

1. Laravel Cloud → **New application** → connect GitHub → pilih repo `myxolorian/LAJU`.
2. Environment pertama: nama `staging`, branch `main`, region terdekat (mis. Singapore).
3. Karena monorepo: set **root directory** ke `BE` (kalau opsi ini tidak tersedia di plan, hubungi support / pakai repo terpisah untuk deploy).
4. PHP **8.3**, Node tidak diperlukan untuk API.

## 2. Database

Attach database (MySQL atau Postgres) ke environment. Cloud menyuntikkan `DB_*` sendiri — jangan diisi manual.
Migrasi sudah diuji di MySQL 8 dan SQLite.

## 3. Environment variables

| Variabel | Nilai | Catatan |
|---|---|---|
| `APP_NAME` | `Laju` | |
| `APP_ENV` | `staging` | |
| `APP_DEBUG` | `false` | **wajib false** — kalau true, error menampilkan stack trace |
| `APP_KEY` | hasil `php artisan key:generate --show` (jalankan lokal, salin) | secret, jangan commit |
| `APP_URL` | URL staging dari Cloud | |
| `LOG_CHANNEL` | `stderr` | supaya log tampil di dashboard Cloud |
| `SESSION_DRIVER`, `CACHE_STORE`, `QUEUE_CONNECTION` | `database` | tabel `sessions`/`cache`/`jobs` sudah ada di migrasi |
| `CORS_ALLOWED_ORIGINS` | URL web staging, mis. `https://laju-staging.example.com` (pisahkan koma untuk beberapa origin) | tanpa ini FE tidak bisa memanggil API |
| `SANCTUM_TOKEN_EXPIRATION` | `43200` | menit |
| `DB_*` | otomatis dari Cloud | |

## 4. Build & deploy commands

- Build: `composer install --no-dev --optimize-autoloader`
- Deploy: `php artisan migrate --force`

(`config:cache`/`route:cache` biasanya sudah dijalankan Cloud; tambahkan bila belum.)

## 5. Deploy & verifikasi

Deploy branch `main`, lalu dari mesin lokal:

```bash
scripts/smoke-api.sh https://<host-staging>
```

Skrip memanggil `/up` → `register-club` → `login` → `me` → `logout`, dan memastikan user hasil register berperan `admin`.
Ia membuat satu klub uji ("Smoke FC") dan satu user `smoke+<timestamp>@example.com` di database staging — hapus manual bila perlu.

Manual via curl:

```bash
curl -X POST https://<host-staging>/api/auth/register-club \
  -H 'Accept: application/json' -H 'Content-Type: application/json' \
  -d '{"club_name":"Klub Uji","sport_type":"renang","name":"Admin","email":"admin@example.com","password":"min-8-karakter","password_confirmation":"min-8-karakter"}'

curl -X POST https://<host-staging>/api/auth/login \
  -H 'Accept: application/json' -H 'Content-Type: application/json' \
  -d '{"email":"admin@example.com","password":"min-8-karakter"}'
```

## 6. Web (di luar T5)

Fase 0 hanya men-deploy `BE/`. Untuk mencoba FE terhadap staging: `VITE_API_URL=https://<host-staging>/api npm run dev` di `FE/`
dan pastikan `http://localhost:5173` ada di `CORS_ALLOWED_ORIGINS`.
