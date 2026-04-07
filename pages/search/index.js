import { useState } from 'react';
import Link from 'next/link';

export default function SearchPage() {
  const [query, setQuery] = useState('');
  const [results, setResults] = useState([]);
  const [message, setMessage] = useState('');

  async function handleSearch(e) {
    e.preventDefault();
    setMessage('Recherche en cours...');

    const res = await fetch('/api/webtoons/search?q=' + encodeURIComponent(query));
    const data = await res.json();
    setResults(data.webtoons || []);
    setMessage(data.message || '');
  }

  async function addToList(webtoonId) {
    const res = await fetch('/api/user-webtoons/add', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ webtoonId }),
    });
    const data = await res.json();
    alert(data.message);
  }

  return (
    <div>
      <h2>Recherche de webtoon</h2>
      <form onSubmit={handleSearch} className="form-inline">
        <input value={query} onChange={(e) => setQuery(e.target.value)} placeholder="Titre du webtoon" required />
        <button type="submit">Rechercher</button>
      </form>
      {message && <p>{message}</p>}
      <div className="grid">
        {results.map((w) => (
          <div key={w.id} className="card">
            {w.coverImage && <img src={w.coverImage} alt={w.title} className="cover" />}
            <h3>{w.title}</h3>
            <p>{w.author || 'Auteur inconnu'}</p>
            <div className="actions">
              <Link href={`/webtoon/${w.id}`}>Voir détail</Link>
              <button onClick={() => addToList(w.id)}>Ajouter à ma liste</button>
            </div>
          </div>
        ))}
      </div>
    </div>
  );
}
