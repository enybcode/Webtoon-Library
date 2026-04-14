const webtoons = [
  { name: "Solo Leveling", status: "Terminé" },
  { name: "Tower of God", status: "En cours" },
  { name: "The Boxer", status: "À lire" }
];

const features = [
  {
    title: "Rechercher un webtoon",
    text: "Trouve rapidement un titre et ajoute-le à ta bibliothèque."
  },
  {
    title: "Suivre ta lecture",
    text: "Mets à jour ton statut : à lire, en cours, terminé ou abandonné."
  },
  {
    title: "Gérer ta collection",
    text: "Retrouve tous tes webtoons dans un espace simple et clair."
  }
];

const statuses = ["À lire", "En cours", "En pause", "Terminé", "Abandonné"];

const webtoonList = document.getElementById("webtoon-list");
const featureCards = document.getElementById("feature-cards");
const statusList = document.getElementById("status-list");

webtoons.forEach((webtoon) => {
  const item = document.createElement("div");
  item.className = "webtoon-item";
  item.innerHTML = `
    <div>
      <h4>${webtoon.name}</h4>
      <p>Suivi personnel</p>
    </div>
    <span class="webtoon-status">${webtoon.status}</span>
  `;
  webtoonList.appendChild(item);
});

features.forEach((feature) => {
  const card = document.createElement("article");
  card.className = "card";
  card.innerHTML = `
    <div class="card-icon"></div>
    <h4>${feature.title}</h4>
    <p>${feature.text}</p>
  `;
  featureCards.appendChild(card);
});

statuses.forEach((status) => {
  const tag = document.createElement("span");
  tag.className = "status-tag";
  tag.textContent = status;
  statusList.appendChild(tag);
});