import { prisma } from '../../../lib/prisma';
import { getUserFromRequest } from '../../../lib/auth';

export default async function handler(req, res) {
  if (req.method !== 'PUT') return res.status(405).json({ message: 'Méthode non autorisée' });

  const user = getUserFromRequest(req);
  if (!user) return res.status(401).json({ message: 'Non autorisé' });

  const { id, readingStatusId, currentChapter, personalNote, rating } = req.body;

  const item = await prisma.userWebtoon.findUnique({ where: { id: Number(id) } });
  if (!item || item.userId !== user.id) return res.status(404).json({ message: 'Introuvable' });

  const updated = await prisma.userWebtoon.update({
    where: { id: Number(id) },
    data: {
      ...(readingStatusId ? { readingStatusId: Number(readingStatusId) } : {}),
      ...(currentChapter !== undefined ? { currentChapter: Number(currentChapter) } : {}),
      ...(personalNote !== undefined ? { personalNote } : {}),
      ...(rating !== undefined ? { rating } : {}),
    },
    include: { webtoon: true, readingStatus: true },
  });

  res.status(200).json({ item: updated });
}
