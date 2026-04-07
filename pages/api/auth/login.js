import bcrypt from 'bcryptjs';
import { serialize } from 'cookie';
import { prisma } from '../../../lib/prisma';
import { signToken } from '../../../lib/auth';

export default async function handler(req, res) {
  if (req.method !== 'POST') return res.status(405).json({ message: 'Méthode non autorisée' });

  const { email, password } = req.body;

  const user = await prisma.user.findUnique({
    where: { email },
    include: { role: true },
  });

  if (!user) return res.status(401).json({ message: 'Identifiants invalides' });
  if (user.isBanned) return res.status(403).json({ message: 'Compte banni' });

  const ok = await bcrypt.compare(password, user.password);
  if (!ok) return res.status(401).json({ message: 'Identifiants invalides' });

  const token = signToken(user);

  res.setHeader('Set-Cookie',
    serialize('token', token, {
      httpOnly: true,
      path: '/',
      maxAge: 60 * 60 * 24 * 7,
      sameSite: 'lax',
    })
  );

  return res.status(200).json({ message: 'Connexion réussie' });
}
