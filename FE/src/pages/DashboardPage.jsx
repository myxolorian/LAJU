import { useAuth } from '../auth/AuthContext'

export default function DashboardPage() {
  const { user, logout } = useAuth()
  const memberships = user.club_members ?? []

  return (
    <main className="page">
      <div className="card wide">
        <header className="topbar">
          <div className="brand">Laju</div>
          <button type="button" className="secondary" onClick={logout}>
            Keluar
          </button>
        </header>

        <h1>Dashboard</h1>

        <dl className="data">
          <dt>Nama</dt>
          <dd>{user.name}</dd>
          <dt>Email</dt>
          <dd>{user.email}</dd>
        </dl>

        {/* 1 klub → auto masuk; >1 → club switcher (menyusul di fase berikutnya). */}
        {memberships.length === 1 && (
          <p>
            Masuk sebagai <strong>{memberships[0].role}</strong> di{' '}
            <strong>{memberships[0].club.name}</strong>.
          </p>
        )}
        {memberships.length > 1 && (
          <p className="muted">Anda tergabung di {memberships.length} klub. Club switcher menyusul.</p>
        )}

        <h2>Keanggotaan klub</h2>
        {memberships.length === 0 ? (
          <p className="muted">Belum tergabung di klub mana pun.</p>
        ) : (
          <table>
            <thead>
              <tr>
                <th>Klub</th>
                <th>Cabang</th>
                <th>Peran</th>
                <th>Status</th>
                <th>Paket</th>
              </tr>
            </thead>
            <tbody>
              {memberships.map((m) => (
                <tr key={m.id}>
                  <td>{m.club.name}</td>
                  <td>{m.club.sport_type}</td>
                  <td className="mono">{m.role}</td>
                  <td className="mono">{m.status}</td>
                  <td>
                    <span className="tag">{m.club.subscription_plan}</span>
                  </td>
                </tr>
              ))}
            </tbody>
          </table>
        )}
      </div>
    </main>
  )
}
