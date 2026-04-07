import { serialize } from 'cookie';

export default function handler(req, res) {
  if (req.method !== 'POST') return res.status(405).json({ message: 'Méthode non autorisée' });

  res.setHeader(
    'Set-Cookie',
    serialize('token', '', {
      httpOnly: true,
      path: '/',
      maxAge: 0,
      sameSite: 'lax',
    })
  );

  res.status(200).json({ message: 'Déconnexion réussie' });
}
