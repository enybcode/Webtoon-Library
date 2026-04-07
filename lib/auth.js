import jwt from 'jsonwebtoken';
import { parse } from 'cookie';

const JWT_SECRET = process.env.JWT_SECRET || 'secret-bts-webtoon';

export function signToken(user) {
  return jwt.sign(
    { id: user.id, email: user.email, role: user.role.name, username: user.username },
    JWT_SECRET,
    { expiresIn: '7d' }
  );
}

export function getUserFromRequest(req) {
  const cookieHeader = req.headers.cookie || '';
  const cookies = parse(cookieHeader);
  const token = cookies.token;

  if (!token) return null;

  try {
    return jwt.verify(token, JWT_SECRET);
  } catch (error) {
    return null;
  }
}

export function getUserFromPageRequest(req) {
  const cookieHeader = req.headers.cookie || '';
  const cookies = parse(cookieHeader);
  const token = cookies.token;
  if (!token) return null;

  try {
    return jwt.verify(token, JWT_SECRET);
  } catch (error) {
    return null;
  }
}
