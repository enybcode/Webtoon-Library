import { prisma } from '../../../lib/prisma';

export default async function handler(req, res) {
  const questions = await prisma.securityQuestion.findMany({ orderBy: { id: 'asc' } });
  res.status(200).json({ questions });
}
