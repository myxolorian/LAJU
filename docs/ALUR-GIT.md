# Alur kerja Git — Laju

Berlaku mulai Fase 1. Fondasi (Fase 0) dibuat langsung di `main` sebagai titik awal; setelah itu setiap fitur punya branch sendiri.

## Prinsip

1. **`main` selalu sehat.** Isinya lolos CI dan siap di-deploy ke staging. Tidak ada push langsung ke `main`.
2. **Satu task = satu branch = satu PR.** Nama branch: `feat/t<nomor>-<slug-singkat>`, mis. `feat/t7-form-pendaftaran`. Nomor = kolom *Urutan* di Papan Tugas Laju (Notion). Perbaikan bug: `fix/…`, pekerjaan non-fitur: `chore/…`.
3. **Branch berumur pendek** (idealnya 1–3 hari). Makin lama hidup, makin besar peluang konflik.
4. **Selalu bercabang dari `main` terbaru**, bukan dari branch fitur orang lain.

## Siklus satu fitur

```bash
# 1. Mulai dari main yang terbaru
git checkout main && git pull
git checkout -b feat/t7-form-pendaftaran

# 2. Kerjakan, commit kecil-kecil
git add -A && git commit -m "feat(t7): endpoint daftar anggota"

# 3. Test lokal (wajib hijau sebelum PR)
cd BE && vendor/bin/pint --test && php artisan test
cd ../FE && npm run lint && npm test && npm run build

# 4. Tarik main terbaru ke branch fitur, lalu test ULANG
git fetch origin
git merge origin/main          # selesaikan konflik di sini, bukan di main
#    (jalankan lagi langkah 3, dan `php artisan migrate` bila ada migrasi baru dari main)

# 5. Push & buka PR ke main
git push -u origin feat/t7-form-pendaftaran
```

Langkah 4 inilah yang Anda maksud dengan "main ditarik ke branch fitur": integrasi diuji di branch fitur, jadi kalau ada masalah, rusaknya di branch fitur dan bukan di `main`.
Pakai `merge`, bukan `rebase` — tidak perlu force-push, lebih aman untuk tim kecil.

## Pull Request

- Judul: `feat(t7): form pendaftaran anggota`. Deskripsi menyebut task id dan link ke Papan Tugas Laju.
- Kecil dan fokus: satu task. Kalau lebih dari ±400 baris berubah, pecah.
- CI harus hijau. Minimal 1 review dari rekan (Amare ↔ Kevin) sebelum merge.
- Merge dengan **Squash and merge**, lalu hapus branch. `main` jadi satu commit per fitur.
- Status Notion: **Sedang Dikerjakan** saat mulai, **Review** saat PR dibuka, **Selesai** saat merge.

## Pembagian kerja agar tidak tabrakan

Fase 1 punya dua track paralel (A: Anggota & Pendaftaran, B: Jadwal, Absensi & Notifikasi). Cara menjaga agar dua orang tidak saling injak:

| Titik rawan | Aturan |
|---|---|
| **Migrasi** | Satu tabel = satu pemilik task. **Migrasi yang sudah ter-merge tidak boleh diedit** — buat migrasi baru. Nama file bertimestamp, jadi dua migrasi berbeda tidak konflik. |
| `routes/api.php` | Konflik paling sering. Tiap modul taruh routes dalam `Route::prefix(...)->group` sendiri; konflik biasanya cukup "simpan keduanya". |
| `composer.lock`, `package-lock.json` | Jangan merge manual. Ambil versi `main`, lalu jalankan ulang `composer update <paket>` / `npm install <paket>`. |
| Tabel bersama (`club_members`, `clubs`) | Kabari yang lain sebelum mengubah skemanya. |
| Halaman & komponen FE | Satu folder per modul (`src/pages/anggota/`, `src/pages/jadwal/`). Komponen bersama diubah lewat PR kecil tersendiri. |

Aturan multi-tenant untuk reviewer: **setiap query data operasional harus difilter `club_id`** (lewat `club_members`). Ini dicek di setiap PR.

## Kalau task B butuh task A yang belum di-merge

Pilih salah satu, urut dari yang disarankan:
1. Selesaikan dan merge A dulu (PR kecil, cepat direview), baru mulai B.
2. Kalau harus paralel: cabangkan B dari branch A dan buka PR B dengan base = branch A. Setelah A di-merge, ubah base PR B ke `main`.

## Hotfix

`git checkout main && git pull && git checkout -b fix/<slug>` → perbaiki + test → PR → merge. Alurnya sama, hanya lebih cepat direview.

## Pengaturan GitHub yang perlu diaktifkan (sekali, oleh pemilik repo)

Settings → Branches → aturan untuk `main`:
- Require a pull request before merging, **1 approval**
- Require status checks: `BE (lint + test)` dan `FE (lint + test + build)`
- Require branches to be up to date before merging
- Block force pushes dan deletions

Settings → General → Pull Requests: hanya izinkan **Squash merging**, aktifkan **Automatically delete head branches**.

## Staging

`main` yang ter-merge = kandidat deploy ke staging Laravel Cloud (`docs/DEPLOY-STAGING.md`). Fitur diuji dulu di lokal dan lewat CI; staging dipakai untuk verifikasi akhir (`scripts/smoke-api.sh`) setelah merge.
