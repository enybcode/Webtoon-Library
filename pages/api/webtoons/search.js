import { prisma } from '../../../lib/prisma';
import { searchAniList } from '../../../lib/anilist';

export default async function handler(req, res) {
  const q = (req.query.q || '').trim();
  if (!q) return res.status(400).json({ message: 'Recherche vide' });

  const localResults = await prisma.webtoon.findMany({
    where: {
      title: { contains: q },
    },
    take: 10,
    orderBy: { createdAt: 'desc' },
  });

  if (localResults.length > 0) {
    return res.status(200).json({
      message: 'Résultats trouvés dans la base locale',
      source: 'local',
      webtoons: localResults,
    });
  }

  const aniListResults = await searchAniList(q);

  const defaultStatus = await prisma.readingStatus.findFirst({ where: { name: 'À lire' } });

  const saved = [];
  for (const item of aniListResults) {
    const title = item.title?.romaji || item.title?.english;
    if (!title) continue;

    const genreName = item.genres?.[0] || 'Action';
    let genre = await prisma.genre.findUnique({ where: { name: genreName } });
    if (!genre) {
      genre = await prisma.genre.create({ data: { name: genreName } });
    }

    const author = item.staff?.edges?.[0]?.node?.name?.full || 'Inconnu';

    const webtoon = await prisma.webtoon.upsert({
      where: { anilistId: item.id },
      update: {
        title,
        synopsis: item.description || null,
        coverImage: item.coverImage?.large || null,
        author,
        totalChapters: item.chapters || null,
        publicationStatus: item.status || null,
        genreId: genre.id,
      },
      create: {
        anilistId: item.id,
        title,
        synopsis: item.description || null,
        coverImage: item.coverImage?.large || null,
        author,
        totalChapters: item.chapters || null,
        publicationStatus: item.status || null,
        genreId: genre.id,
        readingStatusId: defaultStatus?.id,
      },
    });

    saved.push(webtoon);
  }

  return res.status(200).json({
    message: 'Résultats importés depuis AniList',
    source: 'anilist',
    webtoons: saved,
  });
}
