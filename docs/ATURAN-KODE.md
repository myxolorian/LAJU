# Aturan menulis kode — Laju

Berlaku untuk Amare dan Kevin. Tiap aturan disertai **alasannya**, supaya bisa dinilai ulang kalau situasinya berubah.
Kalau ingin mengubah aturan: buka PR ke dokumen ini, bukan diam-diam menyimpang di kode.

Tanda: **[WAJIB]** = dicek saat review, PR tidak di-merge kalau dilanggar. **[SARAN]** = default yang baik, boleh menyimpang kalau ada alasan (tulis di PR).

---

## 1. Multi-tenant — aturan terpenting

Satu instance melayani banyak klub. Kebocoran data antar-klub adalah bug terburuk yang mungkin terjadi di produk ini.

| # | Aturan | Kenapa |
|---|---|---|
| 1.1 | **[WAJIB]** Setiap query data operasional (anggota, tim, jadwal, absensi, iuran, pengumuman) difilter `club_id`. | Tanpa filter, admin klub A bisa membaca data klub B. |
| 1.2 | **[WAJIB]** Jangan pernah mempercayai `club_id` dari body/query request. Klub aktif ditentukan dari keanggotaan user (`club_members`) yang divalidasi di server. | Klien bisa mengirim `club_id` klub lain. |
| 1.3 | **[WAJIB]** Jangan simpan `club_id` di tabel `users`. Peran user selalu lewat `club_members` (role + status). | Satu akun bisa di banyak klub dengan peran berbeda (lihat `docs/SKEMA-DB.md`). |
| 1.4 | **[WAJIB]** Tabel operasional baru menyimpan `club_id`, atau `club_member_id`/relasi yang menuju klub. Tabel lanjutan mereferensi `club_member_id`, bukan `user_id`. | Sesuai ERD; mencegah data peran berbeda saling bocor. |
| 1.5 | **[WAJIB]** Cek peran **dan** status keanggotaan: hanya `status = aktif` yang boleh mengakses fitur. `pending`/`nonaktif`/`alumni` ditolak (403). | Anggota yang belum disetujui atau sudah keluar tidak boleh melihat data klub. |
| 1.6 | **[WAJIB]** Setiap fitur baru punya test isolasi: user klub A meminta data klub B → 403/404, bukan datanya. | Satu-satunya cara memastikan 1.1–1.2 tidak jebol saat kode berubah. |
| 1.7 | **[SARAN]** Fitur pertama Fase 1 membuat satu tempat terpusat (middleware "klub aktif" + trait/scope `BelongsToClub`) supaya filter tidak ditulis manual di tiap controller. Sampai itu ada, filter manual + test 1.6. | Aturan yang bergantung pada ingatan manusia pasti suatu saat terlewat. |

## 2. Kuota Free/Pro

| # | Aturan | Kenapa |
|---|---|---|
| 2.1 | **[WAJIB]** Batas paket (25 anggota, 1 tim, dst. — PRD Bagian 8) didefinisikan di **satu tempat** (config/middleware kuota), bukan angka di controller. | Angka akan berubah setelah validasi harga; harus cukup ubah satu file. |
| 2.2 | **[WAJIB]** Pengecekan membaca `clubs.subscription_plan` dan `subscription_status`, bukan asumsi "semua klub Free". | Paket Pro akan datang; tanpa ini harus refactor. |
| 2.3 | **[WAJIB]** Aksi yang melampaui kuota ditolak dengan pesan jelas (mis. 403 + pesan Indonesia), dan ada test-nya. | UI perlu pesan yang bisa ditampilkan ke pengurus. |

## 3. Backend (BE/ — Laravel)

**Struktur — ikuti pola di modul Auth:**

| # | Aturan | Kenapa |
|---|---|---|
| 3.1 | **[WAJIB]** Controller tipis, satu aksi satu class (invokable) atau resource controller standar. Isinya: terima request → panggil logika → kembalikan response. | Controller gemuk tidak bisa dites dan dipakai ulang. |
| 3.2 | **[WAJIB]** Validasi di `FormRequest` (`app/Http/Requests/<Modul>/`), bukan `$request->validate()` di controller. | Aturan validasi jadi satu tempat yang jelas dan bisa dites. |
| 3.3 | **[WAJIB]** Logika bisnis di `app/Actions/<Nama>.php`. Aksi yang menulis ke **lebih dari satu tabel** dibungkus `DB::transaction` (contoh: `RegisterClub`). | Menjamin tidak ada data setengah jadi (klub tanpa admin). |
| 3.4 | **[WAJIB]** Response lewat `JsonResource` (`app/Http/Resources`). Jangan `return $model` langsung. | Model bisa membawa kolom yang tidak boleh keluar (hash password, data klub lain). |
| 3.5 | **[WAJIB]** Nilai tetap (role, status, plan) pakai PHP Backed Enum di `app/Enums`, di-cast di model. Kolom DB tetap `string`, bukan `ENUM` DB. | Menambah nilai tidak butuh `ALTER TABLE`; portabel MySQL/Postgres/SQLite. |
| 3.6 | **[SARAN]** Nilai domain memakai istilah PRD (Indonesia): `admin/pelatih/anggota`, `aktif/nonaktif/alumni/pending`, `belum/menunggu/lunas/menunggak`. Nama class, method, kolom, tabel: bahasa Inggris. | Konsisten dengan ERD; kode tetap idiomatis Laravel. |
| 3.7 | **[WAJIB]** Eager load relasi yang dipakai di response (`->with()` / `->load()`). | Mencegah N+1 query yang baru terasa saat data klub membesar. |
| 3.8 | **[WAJIB]** Otorisasi lewat Policy/Gate per peran, bukan `if ($user->role === ...)` tersebar. | Aturan siapa-boleh-apa harus terbaca di satu tempat. |
| 3.9 | **[WAJIB]** Kode HTTP konsisten: 200 ok, 201 dibuat, 204 tanpa isi, 401 belum login, 403 dilarang, 404 tidak ada, 422 validasi gagal. | FE menangani error berdasarkan kode; kode acak membuat FE penuh pengecualian. |
| 3.10 | **[WAJIB]** Rute baru dalam `Route::prefix('<modul>')->group(...)` di `routes/api.php`, dilindungi `auth:sanctum`. Endpoint sensitif (login, daftar, reset) diberi `throttle`. | Mengurangi konflik merge; melindungi dari brute force. |

**Migrasi & database:**

| # | Aturan | Kenapa |
|---|---|---|
| 3.11 | **[WAJIB]** Migrasi yang sudah masuk `main` **tidak boleh diedit**. Perubahan = migrasi baru. | Yang lain (dan staging) sudah menjalankannya; mengedit membuat DB berbeda-beda. |
| 3.12 | **[WAJIB]** Foreign key pakai `constrained()` dengan `cascadeOnDelete()`/`nullOnDelete()` yang dipilih sadar. Kolom yang sering difilter (terutama `club_id`) diberi index. | Integritas data dan performa query per-klub. |
| 3.13 | **[WAJIB]** Migrasi harus jalan di MySQL dan SQLite (tanpa SQL mentah spesifik DB). Sertakan `down()`. | Test lokal SQLite, staging MySQL/Postgres. |
| 3.14 | **[WAJIB]** Model memakai `#[Fillable([...])]` eksplisit dan `casts()`; jangan `$guarded = []`. | Mencegah mass-assignment (mis. user mengubah `role` sendiri). |
| 3.15 | **[WAJIB]** Setiap model punya factory; test membuat data lewat factory, bukan insert manual. | Test singkat dan seragam. |

## 4. Frontend (FE/ — React)

| # | Aturan | Kenapa |
|---|---|---|
| 4.1 | **[WAJIB]** Semua panggilan HTTP lewat `src/api/client.js` (`api`) dan fungsi di `src/api/<modul>.js`. Jangan `fetch`/`axios` langsung di komponen. | Token, header, dan penanganan 401 ada di satu tempat. |
| 4.2 | **[WAJIB]** Token hanya diakses lewat `tokenStore`. Jangan simpan data sensitif lain (password, data anggota) di `localStorage`. | `localStorage` terbaca skrip apa pun di halaman. |
| 4.3 | **[WAJIB]** Warna, font, spasi pakai CSS variable dari `src/styles/tokens.css` (`var(--accent)`, dst.). Jangan menulis hex/nama font langsung di komponen. | Brand berubah di satu file; tema konsisten. |
| 4.4 | **[WAJIB]** Struktur: `src/pages/<modul>/` untuk halaman, `src/components/` untuk komponen bersama, `src/api/` untuk pemanggilan API. Komponen `PascalCase.jsx`, util `camelCase.js`. | Dua orang bekerja di modul berbeda tanpa saling menyentuh file. |
| 4.5 | **[WAJIB]** Komponen bersama (dipakai >1 modul) diubah lewat PR kecil tersendiri, bukan diselipkan di PR fitur. | Perubahan di komponen bersama memengaruhi pekerjaan orang lain. |
| 4.6 | **[WAJIB]** Error dari server ditampilkan lewat `errorMessages(error)`; halaman punya keadaan *loading*, *error*, dan *kosong*. | Pengurus klub yang kurang teknis tidak boleh melihat layar putih. |
| 4.7 | **[WAJIB]** Form: setiap input punya `<label htmlFor>`, tombol punya `type`, teks UI berbahasa Indonesia. | Aksesibilitas dan konsistensi. |
| 4.8 | **[SARAN]** Komponen fungsional + hooks; state lokal dulu, naikkan ke context hanya bila dipakai banyak halaman. | Menghindari over-engineering di awal. |
| 4.9 | **[WAJIB]** Tampilan/aksi berdasarkan peran (admin/pelatih/anggota) hanya kenyamanan UX. Keamanan tetap di BE. | Menyembunyikan tombol bukan otorisasi. |

## 5. Testing

| # | Aturan | Kenapa |
|---|---|---|
| 5.1 | **[WAJIB]** Fitur BE baru punya feature test: jalur sukses, validasi gagal (422), belum login (401), peran salah (403), isolasi klub (aturan 1.6). | Ini daftar hal yang paling sering rusak. |
| 5.2 | **[WAJIB]** Bug diperbaiki dengan test yang **gagal dulu**, lalu hijau setelah diperbaiki. | Memastikan bug benar-benar ditangkap dan tidak kembali. |
| 5.3 | **[WAJIB]** Alur FE utama dites dengan Vitest + Testing Library memakai `src/test/mockApi.js`. | Alur login/daftar/jadwal tidak boleh rusak diam-diam. |
| 5.4 | **[WAJIB]** Dilarang men-skip/menghapus test agar CI hijau. Kalau test salah, perbaiki dan jelaskan di PR. | CI hijau yang tidak jujur lebih buruk daripada CI merah. |
| 5.5 | **[WAJIB]** Sebelum PR: `vendor/bin/pint --test && php artisan test` (BE) dan `npm run lint && npm test && npm run build` (FE) semuanya hijau. | CI akan menolak; lebih cepat menemukannya di lokal. |

## 6. Gaya kode & kebersihan

| # | Aturan | Kenapa |
|---|---|---|
| 6.1 | **[WAJIB]** Format otomatis: `vendor/bin/pint` (BE) dan oxlint (FE) — jangan berdebat soal gaya, ikuti alat. Ikuti `.editorconfig`. | Diff review hanya berisi perubahan yang bermakna. |
| 6.2 | **[WAJIB]** Tidak ada `dd()`, `dump()`, `console.log`, kode yang dikomentari, atau file percobaan yang ikut ter-commit. | Sisa debug sering bocor ke staging. |
| 6.3 | **[WAJIB]** Komentar menjelaskan **kenapa**, bukan **apa**. Nama yang jelas lebih baik daripada komentar. Komentar boleh berbahasa Indonesia. | Kode sudah menjelaskan "apa"; alasan bisnis tidak terlihat dari kode. |
| 6.4 | **[SARAN]** Fungsi/method pendek dan satu tujuan. Kalau butuh komentar untuk memisahkan "bagian-bagian" di dalamnya, pecah. | Lebih mudah dites dan direview. |
| 6.5 | **[WAJIB]** Jangan menambah abstraksi "untuk nanti". Bangun yang dibutuhkan task ini; refactor saat pola berulang muncul (kali ketiga). | Abstraksi salah lebih mahal daripada duplikasi kecil. |

## 7. Keamanan & data pribadi

Klub yang dilayani menyimpan data anak-anak (akta lahir, KK, KTP).

| # | Aturan | Kenapa |
|---|---|---|
| 7.1 | **[WAJIB]** **Tidak ada secret di repo**: `.env`, token, kunci API. Env baru → tambahkan ke `.env.example` (tanpa nilai rahasia) dan `docs/DEPLOY-STAGING.md`. | Repo bisa dilihat banyak orang; secret yang ter-commit dianggap bocor. |
| 7.2 | **[WAJIB]** Jangan log password, token, atau isi dokumen identitas. | Log sering lebih mudah diakses daripada database. |
| 7.3 | **[WAJIB]** Upload dokumen (KTP/KK/akta): validasi tipe dan ukuran, simpan di disk **privat**, akses lewat endpoint yang mengecek klub + peran. Jangan di `public/`. | Dokumen identitas tidak boleh bisa dibuka lewat URL tebakan. |
| 7.4 | **[WAJIB]** Query lewat Eloquent/query builder dengan binding. Tidak menyambung string SQL dari input. | Mencegah SQL injection. |
| 7.5 | **[WAJIB]** `APP_DEBUG=false` di staging/production. | Debug true membocorkan stack trace, path, dan detail SQL. |
| 7.6 | **[WAJIB]** Menambah dependency baru disebut di deskripsi PR (nama, alasan, lisensi). | Setiap paket adalah permukaan serangan dan beban pemeliharaan. |

## 8. Commit & PR

| # | Aturan | Kenapa |
|---|---|---|
| 8.1 | **[WAJIB]** Pesan commit: `tipe(t<nomor>): ringkasan` — tipe `feat`, `fix`, `chore`, `docs`, `test`, `refactor`. Contoh: `feat(t7): endpoint daftar anggota`. | Riwayat mudah dicari dan terhubung ke Papan Tugas. |
| 8.2 | **[WAJIB]** Satu PR = satu task. Deskripsi menyebut task id + link Notion, apa yang berubah, dan cara mengujinya. Pakai template PR. | Reviewer tahu apa yang harus diperiksa. |
| 8.3 | **[WAJIB]** Tidak ada push langsung ke `main`; PR direview oleh rekan sebelum merge (lihat `docs/ALUR-GIT.md`). | Dua pasang mata menangkap bug 1.1–1.6. |
| 8.4 | **[WAJIB]** Yang me-review memakai checklist di template PR, terutama bagian multi-tenant. Review menilai kode, bukan orangnya. | Review yang konsisten. |
| 8.5 | **[WAJIB]** Lockfile (`composer.lock`, `package-lock.json`) hanya berubah bila memang menambah/mengubah paket. Jangan resolve konflik lockfile dengan tangan. | Lockfile yang rusak menggagalkan CI dan deploy. |

## 9. Kapan boleh melanggar aturan

Boleh, bila ada alasan kuat — **tulis alasannya di deskripsi PR** dan minta reviewer menyetujuinya secara eksplisit. Pengecualian yang berulang berarti aturannya perlu diubah: usulkan lewat PR ke dokumen ini.
