import Link from 'next/link';

export default function Home() {
  return (
    <div>
      <h1>Webtoon Library</h1>
      <p>Application BTS SIO SLAM pour gérer ses webtoons.</p>
      <div className="actions">
        <Link href="/auth/login" className="btn">Se connecter</Link>
        <Link href="/auth/register" className="btn">Créer un compte</Link>
      </div>
    </div>
  );
}
