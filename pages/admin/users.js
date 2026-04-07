import { useState } from 'react';
import { prisma } from '../../lib/prisma';
import { getUserFromPageRequest } from '../../lib/auth';

export default function AdminUsersPage({ initialUsers }) {
  const [users, setUsers] = useState(initialUsers);

  async function runAction(id, action) {
    const res = await fetch('/api/admin/users', {
      method: 'PUT',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ id, action }),
    });

    if (res.ok) {
      const data = await res.json();
      setUsers(users.map((u) => (u.id === id ? data.user : u)));
    }
  }

  async function deleteUser(id) {
    const res = await fetch('/api/admin/users?id=' + id, { method: 'DELETE' });
    if (res.ok) setUsers(users.filter((u) => u.id !== id));
  }

  return (
    <div>
      <h2>Gestion des utilisateurs</h2>
      {users.map((u) => (
        <div key={u.id} className="card">
          <p>{u.username} ({u.email}) - rôle: {u.role.name}</p>
          <div className="actions">
            <button onClick={() => runAction(u.id, 'promote')}>Promouvoir admin</button>
            <button onClick={() => runAction(u.id, 'ban')}>{u.isBanned ? 'Débannir' : 'Bannir'}</button>
            <button onClick={() => deleteUser(u.id)}>Supprimer</button>
          </div>
        </div>
      ))}
    </div>
  );
}

export async function getServerSideProps({ req }) {
  const user = getUserFromPageRequest(req);
  if (!user || user.role !== 'ADMIN') {
    return { redirect: { destination: '/auth/login', permanent: false } };
  }

  const initialUsers = await prisma.user.findMany({ include: { role: true }, orderBy: { createdAt: 'desc' } });

  return {
    props: {
      initialUsers: JSON.parse(JSON.stringify(initialUsers)),
    },
  };
}
