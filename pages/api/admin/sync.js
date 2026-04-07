import { getUserFromRequest } from '../../../lib/auth';
import { prisma } from '../../../lib/prisma';
import { searchAniList } from '../../../lib/anilist';

export default async function handler(req, res) {
  const user = getUserFromRequest(req);
  if (!user || user.role !== 'ADMIN') return res.status(403).json({ message: 'Interdit' });
  if (req.method !== 'POST') return res.status(405).json({ message: 'Méthode non autorisée' });

  const { query } = req.body;
  const results = await searchAniList(query);

  if (!results.length) return res.status(404).json({ message: 'Aucun résultat AniList' });

  const first = results[0];
  const title = first.title?.romaji || first.title?.english;
  const genreName = first.genres?.[0] || 'Action';
  const author = first.staff?.edges?.[0]?.node?.name?.full || 'Inconnu';

  let genre = await prisma.genre.findUnique({ where: { name: genreName } });
  if (!genre) genre = await prisma.genre.create({ data: { name: genreName } });

  await prisma.webtoon.upsert({
    where: { anilistId: first.id },
    update: {
      title,
      synopsis: first.description || null,
      coverImage: first.coverImage?.large || null,
      author,
      totalChapters: first.chapters || null,
      publicationStatus: first.status || null,
      genreId: genre.id,
    },
    create: {
      anilistId: first.id,
      title,
      synopsis: first.description || null,
      coverImage: first.coverImage?.large || null,
      author,
      totalChapters: first.chapters || null,
      publicationStatus: first.status || null,
      genreId: genre.id,
    },
  });

  return res.status(200).json({ message: 'Synchronisation terminée' });
}
