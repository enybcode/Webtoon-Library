import Link from 'next/link';
import { useEffect, useState } from 'react';

export default function Layout({ children }) {
  const [user, setUser] = useState(null);

  useEffect(() => {
    fetch('/api/auth/me')
      .then((res) => res.json())
      .then((data) => setUser(data.user || null))
      .catch(() => setUser(null));
  }, []);

  async function handleLogout() {
    await fetch('/api/auth/logout', { method: 'POST' });
    window.location.href = '/';
  }

  return (
    <div>
      <nav className="nav">
        <Link href="/">Accueil</Link>
        <Link href="/search">Recherche</Link>
        {user && <Link href="/dashboard">Dashboard</Link>}
        {user && <Link href="/profile">Profil</Link>}
        {user?.role === 'ADMIN' && <Link href="/admin">Admin</Link>}
        {!user && <Link href="/auth/login">Connexion</Link>}
        {!user && <Link href="/auth/register">Inscription</Link>}
        {user && <button onClick={handleLogout}>Déconnexion</button>}
      </nav>
      <main className="container">{children}</main>
    </div>
  );
}
