## Task

Papan Tugas Laju: <!-- link halaman Notion + id task, mis. T7 -->

## Apa yang berubah

<!-- 2–4 poin -->

## Cara menguji

<!-- langkah singkat / perintah -->

## Checklist penulis

- [ ] `vendor/bin/pint --test` + `php artisan test` hijau (BE) dan `npm run lint` + `npm test` + `npm run build` hijau (FE)
- [ ] Sudah `git merge origin/main` ke branch ini dan test diulang
- [ ] Tidak ada secret, `dd()`, `console.log`, atau kode yang dikomentari
- [ ] Env / dependency baru dicantumkan di bawah

## Checklist reviewer (lihat docs/ATURAN-KODE.md)

- [ ] **Multi-tenant:** semua query difilter `club_id`; `club_id` tidak diambil dari request; ada test isolasi klub
- [ ] Peran **dan** status keanggotaan (`aktif`) dicek; ada test 401/403
- [ ] Controller tipis; validasi di FormRequest; logika di Action (+ transaksi bila >1 tabel); response lewat Resource
- [ ] Migrasi baru tidak mengedit migrasi yang sudah di-merge, jalan di MySQL & SQLite
- [ ] Kuota Free/Pro tidak di-hardcode di controller
- [ ] FE: HTTP lewat `src/api`, warna/font lewat token, ada state loading/error/kosong

## Catatan tambahan

<!-- env baru, dependency baru (nama + alasan), pengecualian aturan + alasannya -->
