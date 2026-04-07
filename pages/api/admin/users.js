import { prisma } from '../../../lib/prisma';
import { getUserFromRequest } from '../../../lib/auth';

export default async function handler(req, res) {
  const user = getUserFromRequest(req);
  if (!user || user.role !== 'ADMIN') return res.status(403).json({ message: 'Interdit' });

  if (req.method === 'PUT') {
    const { id, action } = req.body;
    let updated;

    if (action === 'promote') {
      const adminRole = await prisma.role.findUnique({ where: { name: 'ADMIN' } });
      updated = await prisma.user.update({
        where: { id: Number(id) },
        data: { roleId: adminRole.id },
        include: { role: true },
      });
    }

    if (action === 'ban') {
      const current = await prisma.user.findUnique({ where: { id: Number(id) } });
      updated = await prisma.user.update({
        where: { id: Number(id) },
        data: { isBanned: !current.isBanned },
        include: { role: true },
      });
    }

    return res.status(200).json({ user: updated });
  }

  if (req.method === 'DELETE') {
    await prisma.user.delete({ where: { id: Number(req.query.id) } });
    return res.status(200).json({ message: 'Utilisateur supprimé' });
  }

  return res.status(405).json({ message: 'Méthode non autorisée' });
}
