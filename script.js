const webtoons = [
  { name: "Solo Leveling", status: "Terminé" },
  { name: "Tower of God", status: "En cours" },
  { name: "The Boxer", status: "À lire" }
];

const features = [
  {
    title: "Une bibliothèque claire",
    text: "Retrouve facilement tous tes webtoons préférés dans un seul espace."
  },
  {
    title: "Un suivi simple",
    text: "Garde un œil sur ta progression et reprends ta lecture sans te perdre."
  },
  {
    title: "Une expérience agréable",
    text: "Profite d’un site pensé pour aller à l’essentiel avec une lecture plus fluide."
  }
];

const statuses = ["À lire", "En cours", "En pause", "Terminé", "Abandonné"];

const webtoonList = document.getElementById("webtoon-list");
const featureCards = document.getElementById("feature-cards");
const statusList = document.getElementById("status-list");

if (webtoonList) {
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
}

if (featureCards) {
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
}

if (statusList) {
  statuses.forEach((status) => {
    const tag = document.createElement("span");
    tag.className = "status-tag";
    tag.textContent = status;
    statusList.appendChild(tag);
  });
}