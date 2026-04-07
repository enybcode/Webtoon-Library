import { prisma } from '../../../lib/prisma';
import { getUserFromRequest } from '../../../lib/auth';

export default async function handler(req, res) {
  if (req.method !== 'PUT') return res.status(405).json({ message: 'Méthode non autorisée' });

  const user = getUserFromRequest(req);
  if (!user) return res.status(401).json({ message: 'Non autorisé' });

  const { username, avatar } = req.body;

  await prisma.user.update({
    where: { id: user.id },
    data: { username, avatar },
  });

  return res.status(200).json({ message: 'Profil mis à jour' });
}
