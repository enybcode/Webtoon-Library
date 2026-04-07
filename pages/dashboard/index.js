import { getUserFromPageRequest } from '../../lib/auth';
import { prisma } from '../../lib/prisma';
import { useState } from 'react';

const statusOrder = ['À lire', 'En cours', 'En pause', 'Terminé', 'Abandonné'];

export default function DashboardPage({ library, statuses }) {
  const [items, setItems] = useState(library);

  async function updateItem(id, body) {
    const res = await fetch('/api/user-webtoons/update', {
      method: 'PUT',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ id, ...body }),
    });

    if (res.ok) {
      const data = await res.json();
      setItems(items.map((item) => (item.id === id ? data.item : item)));
    }
  }

  return (
    <div>
      <h2>Ma bibliothèque</h2>
      {statusOrder.map((statusName) => {
        const group = items.filter((item) => item.readingStatus.name === statusName);
        if (!group.length) return null;

        return (
          <section key={statusName}>
            <h3>{statusName}</h3>
            {group.map((item) => (
              <div className="card" key={item.id}>
                <strong>{item.webtoon.title}</strong>
                <div className="row">
                  <label>Statut :</label>
                  <select
                    value={item.readingStatusId}
                    onChange={(e) => updateItem(item.id, { readingStatusId: Number(e.target.value) })}
                  >
                    {statuses.map((s) => (
                      <option key={s.id} value={s.id}>{s.name}</option>
                    ))}
                  </select>
                </div>
                <div className="row">
                  <label>Chapitre actuel :</label>
                  <input
                    type="number"
                    min="0"
                    value={item.currentChapter}
                    onChange={(e) => updateItem(item.id, { currentChapter: Number(e.target.value) })}
                  />
                  <button onClick={() => updateItem(item.id, { currentChapter: item.currentChapter + 1 })}>+1 chapitre</button>
                </div>
                <div className="row">
                  <label>Note perso :</label>
                  <input
                    value={item.personalNote || ''}
                    onChange={(e) => updateItem(item.id, { personalNote: e.target.value })}
                  />
                </div>
                <div className="row">
                  <label>Évaluation (1-10) :</label>
                  <input
                    type="number"
                    min="1"
                    max="10"
                    value={item.rating || ''}
                    onChange={(e) => updateItem(item.id, { rating: Number(e.target.value) || null })}
                  />
                </div>
              </div>
            ))}
          </section>
        );
      })}
    </div>
  );
}

export async function getServerSideProps({ req }) {
  const user = getUserFromPageRequest(req);
  if (!user) {
    return { redirect: { destination: '/auth/login', permanent: false } };
  }

  const library = await prisma.userWebtoon.findMany({
    where: { userId: user.id },
    include: {
      webtoon: true,
      readingStatus: true,
    },
    orderBy: { addedAt: 'desc' },
  });

  const statuses = await prisma.readingStatus.findMany({ orderBy: { id: 'asc' } });

  return {
    props: {
      library: JSON.parse(JSON.stringify(library)),
      statuses,
    },
  };
}
