# Laju

SaaS manajemen klub olahraga Indonesia. Multi-tenant (satu instance melayani banyak klub).

```
LAJU/
├── BE/                    Laravel 13 (REST API + Sanctum)
├── FE/                    React 19 + Vite
├── docs/                  PRD Teknis v0.3 & ERD
└── .github/workflows/     CI
```

## Prasyarat

- PHP 8.3+, Composer 2 (ekstensi: mbstring, pdo_mysql, pdo_sqlite, intl, bcmath)
- MySQL (mis. lewat Laragon — nyalakan MySQL dari Laragon sebelum `php artisan migrate`)
- Node 22.12+ (lihat `.nvmrc`), npm

`pdo_sqlite` tetap dipakai untuk test (`phpunit.xml` mengarah ke SQLite in-memory, terpisah dari DB dev), jadi dev lokal boleh pakai MySQL tanpa mengubah cara test jalan.

## Jalankan lokal

```bash
# Nyalakan MySQL (mis. lewat aplikasi Laragon), lalu buat database sekali:
#   mysql -uroot -e "CREATE DATABASE laju CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"

# API  → http://localhost:8000
cd BE
composer install
cp .env.example .env   # sudah default ke MySQL: DB_DATABASE=laju, DB_USERNAME=root, DB_PASSWORD=
php artisan key:generate
php artisan migrate
php artisan serve

# Web  → http://localhost:5173
cd FE
npm install
npm run dev
```

Tes manual API tanpa Postman diinstal: `docs/postman/Laju-API.postman_collection.json` (import ke Postman), atau `bash scripts/smoke-api.sh http://localhost:8000` dari terminal.

## Lint & test

```bash
cd BE && vendor/bin/pint --test && php artisan test
cd FE && npm run lint && npm test && npm run build
```

CI (`.github/workflows/ci.yml`) menjalankan hal yang sama di setiap PR.

## Alur Git

Fondasi (Fase 0) dibuat langsung di `main`. Setelah itu: satu branch per fitur, nama pakai nomor task di Papan Tugas Laju (Notion), mis. `feat/t7-form-pendaftaran`.
Semua perubahan masuk ke `main` lewat Pull Request yang direview; tidak ada push langsung ke `main`.
Detail lengkap: [docs/ALUR-GIT.md](docs/ALUR-GIT.md). Aturan menulis kode: [docs/ATURAN-KODE.md](docs/ATURAN-KODE.md).
