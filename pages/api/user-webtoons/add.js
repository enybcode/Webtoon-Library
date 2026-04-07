import { prisma } from '../../../lib/prisma';
import { getUserFromRequest } from '../../../lib/auth';

export default async function handler(req, res) {
  if (req.method !== 'POST') return res.status(405).json({ message: 'Méthode non autorisée' });

  const user = getUserFromRequest(req);
  if (!user) return res.status(401).json({ message: 'Connecte-toi d abord' });

  const { webtoonId } = req.body;

  const status = await prisma.readingStatus.findUnique({ where: { name: 'À lire' } });

  try {
    await prisma.userWebtoon.create({
      data: {
        userId: user.id,
        webtoonId: Number(webtoonId),
        readingStatusId: status.id,
      },
    });

    const notifType = await prisma.notifType.findUnique({ where: { name: 'WEBTOON' } });
    await prisma.notification.create({
      data: {
        userId: user.id,
        notifTypeId: notifType.id,
        message: 'Un webtoon a été ajouté dans ta liste.',
      },
    });

    return res.status(201).json({ message: 'Webtoon ajouté à ta liste' });
  } catch (error) {
    return res.status(400).json({ message: 'Déjà présent dans ta liste' });
  }
}
