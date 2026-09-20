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

- PHP 8.3+, Composer 2 (ekstensi: mbstring, pdo_sqlite, intl, bcmath)
- Node 22.12+ (lihat `.nvmrc`), npm

## Jalankan lokal

```bash
# API  → http://localhost:8000
cd BE
composer install
cp .env.example .env
php artisan key:generate
touch database/database.sqlite   # dev memakai SQLite
php artisan migrate
php artisan serve

# Web  → http://localhost:5173
cd FE
npm install
npm run dev
```

## Lint & test

```bash
cd BE && vendor/bin/pint --test && php artisan test
cd FE && npm run lint && npm test && npm run build
```

CI (`.github/workflows/ci.yml`) menjalankan hal yang sama di setiap PR.

## Alur Git

Fondasi (Fase 0) dibuat langsung di `main`. Setelah itu: satu branch per fitur, nama pakai nomor task di Papan Tugas Laju (Notion), mis. `feat/t7-form-pendaftaran`.
Semua perubahan masuk ke `main` lewat Pull Request yang direview; tidak ada push langsung ke `main`.
Detail lengkap: [docs/ALUR-GIT.md](docs/ALUR-GIT.md).
