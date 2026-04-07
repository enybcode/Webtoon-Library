import { useState } from 'react';
import { prisma } from '../../lib/prisma';
import { getUserFromPageRequest } from '../../lib/auth';

export default function CatalogPage({ initialWebtoons }) {
  const [webtoons, setWebtoons] = useState(initialWebtoons);
  const [form, setForm] = useState({ title: '', author: '' });

  async function addWebtoon(e) {
    e.preventDefault();
    const res = await fetch('/api/admin/catalog', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify(form),
    });
    const data = await res.json();
    if (res.ok) {
      setWebtoons([data.webtoon, ...webtoons]);
      setForm({ title: '', author: '' });
    }
  }

  async function deleteWebtoon(id) {
    const res = await fetch('/api/admin/catalog?id=' + id, { method: 'DELETE' });
    if (res.ok) {
      setWebtoons(webtoons.filter((w) => w.id !== id));
    }
  }

  return (
    <div>
      <h2>Gestion du catalogue</h2>
      <form onSubmit={addWebtoon} className="form-inline">
        <input value={form.title} onChange={(e) => setForm({ ...form, title: e.target.value })} placeholder="Titre" required />
        <input value={form.author} onChange={(e) => setForm({ ...form, author: e.target.value })} placeholder="Auteur" required />
        <button type="submit">Ajouter</button>
      </form>
      {webtoons.map((w) => (
        <div key={w.id} className="card row-between">
          <span>{w.title} - {w.author || 'Inconnu'}</span>
          <button onClick={() => deleteWebtoon(w.id)}>Supprimer</button>
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

  const initialWebtoons = await prisma.webtoon.findMany({ orderBy: { createdAt: 'desc' } });

  return { props: { initialWebtoons } };
}
