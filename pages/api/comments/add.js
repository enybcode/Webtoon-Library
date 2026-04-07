import { prisma } from '../../../lib/prisma';
import { getUserFromRequest } from '../../../lib/auth';

export default async function handler(req, res) {
  if (req.method !== 'POST') return res.status(405).json({ message: 'Méthode non autorisée' });

  const user = getUserFromRequest(req);
  if (!user) return res.status(401).json({ message: 'Connexion requise' });

  const { webtoonId, content } = req.body;

  const comment = await prisma.comment.create({
    data: {
      userId: user.id,
      webtoonId: Number(webtoonId),
      content,
    },
    include: {
      user: { select: { username: true } },
    },
  });

  res.status(201).json({ comment });
}
