import { prisma } from '../../lib/prisma';
import { getUserFromPageRequest } from '../../lib/auth';
import { useState } from 'react';

export default function WebtoonDetail({ webtoon, comments, canComment }) {
  const [content, setContent] = useState('');
  const [allComments, setAllComments] = useState(comments);

  async function handleComment(e) {
    e.preventDefault();
    const res = await fetch('/api/comments/add', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ webtoonId: webtoon.id, content }),
    });

    if (res.ok) {
      const data = await res.json();
      setAllComments([data.comment, ...allComments]);
      setContent('');
    }
  }

  async function addToList() {
    const res = await fetch('/api/user-webtoons/add', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ webtoonId: webtoon.id }),
    });
    const data = await res.json();
    alert(data.message);
  }

  return (
    <div>
      <h2>{webtoon.title}</h2>
      {webtoon.coverImage && <img src={webtoon.coverImage} alt={webtoon.title} className="cover" />}
      <p><strong>Auteur :</strong> {webtoon.author || 'Inconnu'}</p>
      <p><strong>Chapitres :</strong> {webtoon.totalChapters || 'N/A'}</p>
      <p><strong>Statut publication :</strong> {webtoon.publicationStatus || 'N/A'}</p>
      <p>{webtoon.synopsis || 'Pas de synopsis.'}</p>
      <button onClick={addToList}>Ajouter à ma liste</button>

      <section>
        <h3>Commentaires</h3>
        {canComment && (
          <form onSubmit={handleComment} className="form">
            <textarea value={content} onChange={(e) => setContent(e.target.value)} required />
            <button type="submit">Ajouter commentaire</button>
          </form>
        )}
        {allComments.map((c) => (
          <div key={c.id} className="card">
            <p><strong>{c.user.username}</strong> - {new Date(c.createdAt).toLocaleString()}</p>
            <p>{c.content}</p>
          </div>
        ))}
      </section>
    </div>
  );
}

export async function getServerSideProps({ params, req }) {
  const webtoon = await prisma.webtoon.findUnique({ where: { id: Number(params.id) } });

  if (!webtoon) return { notFound: true };

  const comments = await prisma.comment.findMany({
    where: { webtoonId: webtoon.id },
    include: { user: { select: { username: true } } },
    orderBy: { createdAt: 'desc' },
  });

  const user = getUserFromPageRequest(req);

  return {
    props: {
      webtoon,
      comments: JSON.parse(JSON.stringify(comments)),
      canComment: !!user,
    },
  };
}
