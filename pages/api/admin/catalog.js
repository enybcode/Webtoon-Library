import { prisma } from '../../../lib/prisma';
import { getUserFromRequest } from '../../../lib/auth';

export default async function handler(req, res) {
  const user = getUserFromRequest(req);
  if (!user || user.role !== 'ADMIN') return res.status(403).json({ message: 'Interdit' });

  if (req.method === 'POST') {
    const { title, author } = req.body;
    const defaultStatus = await prisma.readingStatus.findUnique({ where: { name: 'À lire' } });
    const genre = await prisma.genre.findFirst();

    const webtoon = await prisma.webtoon.create({
      data: { title, author, readingStatusId: defaultStatus?.id, genreId: genre?.id },
    });
    return res.status(201).json({ webtoon });
  }

  if (req.method === 'DELETE') {
    await prisma.webtoon.delete({ where: { id: Number(req.query.id) } });
    return res.status(200).json({ message: 'Supprimé' });
  }

  return res.status(405).json({ message: 'Méthode non autorisée' });
}
