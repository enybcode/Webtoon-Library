import Link from 'next/link';
import { getUserFromPageRequest } from '../../lib/auth';

export default function AdminPage() {
  return (
    <div>
      <h2>Dashboard Admin</h2>
      <p>Gestion rapide du projet.</p>
      <ul>
        <li><Link href="/admin/catalog">Gérer le catalogue webtoon</Link></li>
        <li><Link href="/admin/users">Gérer les utilisateurs</Link></li>
        <li><Link href="/admin/sync">Synchronisation AniList</Link></li>
      </ul>
    </div>
  );
}

export async function getServerSideProps({ req }) {
  const user = getUserFromPageRequest(req);
  if (!user || user.role !== 'ADMIN') {
    return { redirect: { destination: '/auth/login', permanent: false } };
  }

  return { props: {} };
}
