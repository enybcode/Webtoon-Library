const { PrismaClient } = require('@prisma/client');

const prisma = new PrismaClient();

async function main() {
  await prisma.role.createMany({
    data: [{ name: 'USER' }, { name: 'ADMIN' }],
    skipDuplicates: true,
  });

  await prisma.securityQuestion.createMany({
    data: [
      { question: 'Quel est le nom de ton premier animal ?' },
      { question: 'Dans quelle ville es-tu né ?' },
      { question: 'Quel est ton plat préféré ?' },
    ],
    skipDuplicates: true,
  });

  await prisma.readingStatus.createMany({
    data: [
      { name: 'À lire' },
      { name: 'En cours' },
      { name: 'En pause' },
      { name: 'Terminé' },
      { name: 'Abandonné' },
    ],
    skipDuplicates: true,
  });

  await prisma.genre.createMany({
    data: [{ name: 'Action' }, { name: 'Romance' }, { name: 'Fantasy' }, { name: 'Slice of Life' }],
    skipDuplicates: true,
  });

  await prisma.notifType.createMany({
    data: [{ name: 'INFO' }, { name: 'SYSTEM' }, { name: 'WEBTOON' }],
    skipDuplicates: true,
  });
}

main()
  .then(async () => {
    await prisma.$disconnect();
  })
  .catch(async (e) => {
    console.error(e);
    await prisma.$disconnect();
    process.exit(1);
  });
