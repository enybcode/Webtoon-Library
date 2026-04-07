import { useState } from 'react';
import { getUserFromPageRequest } from '../../lib/auth';

export default function SyncPage() {
  const [query, setQuery] = useState('');
  const [message, setMessage] = useState('');

  async function syncOne(e) {
    e.preventDefault();
    const res = await fetch('/api/admin/sync', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ query }),
    });
    const data = await res.json();
    setMessage(data.message);
  }

  return (
    <div>
      <h2>Synchronisation AniList</h2>
      <form onSubmit={syncOne} className="form-inline">
        <input value={query} onChange={(e) => setQuery(e.target.value)} placeholder="Nom du webtoon" required />
        <button type="submit">Synchroniser</button>
      </form>
      {message && <p>{message}</p>}
    </div>
  );
}

export async function getServerSideProps({ req }) {
  const user = getUserFromPageRequest(req);
  if (!user || user.role !== 'ADMIN') {
    return { redirect: { destination: '/auth/login', permanent: false } };
  }

  return { props: {} };
}
