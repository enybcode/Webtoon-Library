# Webtoon Library - Projet BTS SIO SLAM

Application web de gestion de webtoons (Next.js + Prisma + SQLite).
Le style du projet reste volontairement simple pour être défendable à l'oral de BTS.

## 1) Arborescence du projet

```txt
Webtoon-Library/
├─ components/
│  └─ Layout.js
├─ lib/
│  ├─ anilist.js
│  ├─ auth.js
│  └─ prisma.js
├─ pages/
│  ├─ api/
│  │  ├─ admin/
│  │  │  ├─ catalog.js
│  │  │  ├─ sync.js
│  │  │  └─ users.js
│  │  ├─ auth/
│  │  │  ├─ login.js
│  │  │  ├─ logout.js
│  │  │  ├─ me.js
│  │  │  ├─ questions.js
│  │  │  ├─ register.js
│  │  │  └─ update-profile.js
│  │  ├─ comments/
│  │  │  └─ add.js
│  │  ├─ user-webtoons/
│  │  │  ├─ add.js
│  │  │  └─ update.js
│  │  └─ webtoons/
│  │     └─ search.js
│  ├─ admin/
│  │  ├─ catalog.js
│  │  ├─ index.js
│  │  ├─ sync.js
│  │  └─ users.js
│  ├─ auth/
│  │  ├─ login.js
│  │  └─ register.js
│  ├─ dashboard/
│  │  └─ index.js
│  ├─ profile/
│  │  └─ index.js
│  ├─ search/
│  │  └─ index.js
│  ├─ webtoon/
│  │  └─ [id].js
│  ├─ _app.js
│  └─ index.js
├─ prisma/
│  ├─ schema.prisma
│  └─ seed.js
├─ styles/
│  └─ globals.css
├─ .env.example
├─ next.config.js
└─ package.json
```

### Rôle des dossiers
- `pages/` : pages front et routes API intégrées Next.js.
- `lib/` : fonctions utilitaires (auth, Prisma, AniList).
- `prisma/` : schéma de base et script de données de départ.
- `components/` : composants réutilisables (layout/navigation).
- `styles/` : style global responsive simple.

---

## 2) Schéma de base de données

Le schéma Prisma contient les entités demandées :
- Users
- Role
- SecurityQuestion
- Webtoon
- Genre
- ReadingStatus
- UserWebtoon
- Comment
- Notification
- NotifType

Relations respectées :
- 1 rôle -> plusieurs users
- 1 question secrète -> plusieurs users
- 1 genre -> plusieurs webtoons
- n-n Users <-> Webtoon via UserWebtoon
- commentaires liés à User + Webtoon
- notifications liées à User + NotifType

---

## 3) Authentification

Fonctionnalités implémentées :
- inscription (`/auth/register`)
- connexion (`/auth/login`)
- déconnexion (`/api/auth/logout`)
- session via JWT en cookie HTTP-only
- mot de passe chiffré avec bcrypt
- protection de routes via `getServerSideProps`

---

## 4) Recherche AniList (obligatoire)

Route : `GET /api/webtoons/search?q=...`

Logique :
1. recherche locale en base (`Webtoon.title`)
2. si vide : appel AniList GraphQL
3. récupération des champs utiles
4. sauvegarde locale (upsert)
5. renvoi des données à l'utilisateur

---

## 5) Bibliothèque utilisateur

Fonctionnalités :
- ajout à la liste (`/api/user-webtoons/add`)
- affichage dashboard par statut
- changement de statut
- suivi chapitre (manuel + bouton `+1`)
- note personnelle
- évaluation sur 10

---

## 6) Commentaires

Fonctionnalités :
- page détail webtoon avec liste commentaires
- ajout commentaire connecté (`/api/comments/add`)

---

## 7) Back-office admin

Pages admin :
- `/admin` : dashboard
- `/admin/catalog` : CRUD simple du catalogue
- `/admin/users` : gestion utilisateurs (promotion admin, ban/déban, suppression)
- `/admin/sync` : synchronisation AniList d'un webtoon

Contrôle d'accès : rôle `ADMIN` obligatoire.

---

## 8) Installation et lancement

### Prérequis
- Node.js 18+

### Commandes
```bash
npm install
cp .env.example .env
npx prisma generate
npx prisma migrate dev --name init
node prisma/seed.js
npm run dev
```

Application : `http://localhost:3000`

---

## Compte admin (à créer facilement)

1. Inscrire un premier compte normalement.
2. Dans la base SQLite, passer son rôle en `ADMIN` (ou faire une route de test temporaire).

---

## Remarque BTS

Le projet est volontairement :
- lisible
- simple à expliquer
- structuré sans sur-ingénierie
- cohérent pour une soutenance de BTS SIO SLAM
