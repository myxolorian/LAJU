# Skema DB — Fase 0 (4 tabel inti)

Subset dari ERD 14 entitas (`ERD_Laju_v0.3.png`, PRD Teknis v0.3 Bagian 7).

| Tabel | Kolom | Catatan |
|---|---|---|
| `users` | id, name, email (unique), phone?, password, timestamps (+ kolom bawaan Laravel: email_verified_at, remember_token) | **Tidak ada `club_id`.** Identitas login global. `password` = `password_hash` di ERD (nama bawaan Laravel). |
| `clubs` | id, name, sport_type, logo_url?, subscription_plan (`free`\|`pro`, default `free`), subscription_status (`active`\|`past_due`\|`cancelled`\|`expired`, default `active`), timestamps | Dibaca middleware kuota Free/Pro (PRD Bagian 8). |
| `club_members` | id, user_id → users, club_id → clubs, role (`admin`\|`pelatih`\|`anggota`), status (`aktif`\|`nonaktif`\|`alumni`\|`pending`, default `pending`), joined_at?, timestamps | Pivot multi-tenant. `unique(user_id, club_id)`. Index `(club_id, role)` dan `(club_id, status)` untuk hitung kuota. |
| `teams` | id, club_id → clubs, name, category?, timestamps | `unique(club_id, name)`. |

## Keputusan desain

- **`role` / `status` / `subscription_*` disimpan sebagai `string`, bukan `ENUM` DB.** Nilai valid didefinisikan di `app/Enums/*` dan di-cast di model. Menambah nilai baru (mis. status langganan) tidak butuh `ALTER TABLE`, dan portabel antar MySQL/Postgres/SQLite.
- **`club_members` punya `id` sendiri.** Tabel lanjutan (`team_members`, `attendances`, `dues`) sesuai ERD mereferensi `club_member_id`, bukan `user_id`, supaya satu akun dengan peran berbeda di beberapa klub tidak saling bocor datanya.
- **`joined_at` nullable**: baris `pending` (menunggu approval admin) belum punya tanggal bergabung.
- **`ON DELETE CASCADE`** dari `clubs` dan `users` ke `club_members`, dan dari `clubs` ke `teams`.
- `created_at`/`updated_at` ditambahkan di semua tabel (ERD hanya menampilkan `created_at` di sebagian tabel).

## Migrasi lanjutan (tinggal nyambung)

`team_members` (team_id, club_member_id, role_in_team), `schedules` (team_id → teams), `sessions`, `attendances`, `dues`,
`announcements`, `notifications`, `registration_links`, `subscription_payments` (club_id → clubs), `platform_admins` (user_id → users).
Semuanya hanya menambah tabel baru yang mengacu ke 4 tabel di atas; tidak ada perubahan pada tabel inti.
