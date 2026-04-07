import bcrypt from 'bcryptjs';
import { prisma } from '../../../lib/prisma';

export default async function handler(req, res) {
  if (req.method !== 'POST') return res.status(405).json({ message: 'Méthode non autorisée' });

  const { username, email, password, securityQuestionId, securityAnswer } = req.body;

  if (!username || !email || !password || !securityQuestionId || !securityAnswer) {
    return res.status(400).json({ message: 'Tous les champs sont obligatoires' });
  }

  const existing = await prisma.user.findUnique({ where: { email } });
  if (existing) return res.status(400).json({ message: 'Email déjà utilisé' });

  const role = await prisma.role.findUnique({ where: { name: 'USER' } });
  const hash = await bcrypt.hash(password, 10);

  await prisma.user.create({
    data: {
      username,
      email,
      password: hash,
      roleId: role.id,
      securityQuestionId: Number(securityQuestionId),
      securityAnswer,
    },
  });

  return res.status(201).json({ message: 'Compte créé avec succès' });
}
