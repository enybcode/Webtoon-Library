import { useState } from 'react';
import { getUserFromPageRequest } from '../../lib/auth';
import { prisma } from '../../lib/prisma';

export default function ProfilePage({ user, notifications, libraryCount }) {
  const [form, setForm] = useState({ username: user.username, avatar: user.avatar || '' });
  const [message, setMessage] = useState('');

  async function saveProfile(e) {
    e.preventDefault();

    const res = await fetch('/api/auth/update-profile', {
      method: 'PUT',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify(form),
    });

    const data = await res.json();
    setMessage(data.message);
  }

  return (
    <div>
      <h2>Mon profil</h2>
      <p>Pseudo : {user.username}</p>
      <p>Email : {user.email}</p>
      <p>Webtoons dans la bibliothèque : {libraryCount}</p>
      {user.avatar && <img src={user.avatar} alt="avatar" className="avatar" />}

      <form onSubmit={saveProfile} className="form">
        <input
          value={form.username}
          onChange={(e) => setForm({ ...form, username: e.target.value })}
          placeholder="Pseudo"
        />
        <input
          value={form.avatar}
          onChange={(e) => setForm({ ...form, avatar: e.target.value })}
          placeholder="URL avatar"
        />
        <button type="submit">Mettre à jour</button>
      </form>

      {message && <p>{message}</p>}

      <h3>Notifications</h3>
      {notifications.map((n) => (
        <div className="card" key={n.id}>{n.message}</div>
      ))}
    </div>
  );
}

export async function getServerSideProps({ req }) {
  const session = getUserFromPageRequest(req);
  if (!session) return { redirect: { destination: '/auth/login', permanent: false } };

  const user = await prisma.user.findUnique({ where: { id: session.id } });
  const notifications = await prisma.notification.findMany({
    where: { userId: session.id },
    orderBy: { createdAt: 'desc' },
    take: 10,
  });
  const libraryCount = await prisma.userWebtoon.count({ where: { userId: session.id } });

  return {
    props: {
      user,
      notifications: JSON.parse(JSON.stringify(notifications)),
      libraryCount,
    },
  };
}
