// =============================================
// script.js — JavaScript de l'application
// =============================================

// ===== CONFIRMATION AVANT SUPPRESSION =====
// Cette fonction est appelée quand on clique sur "Supprimer"
function confirmerSuppression(lien) {
    // On demande confirmation à l'utilisateur
    if (confirm("Êtes-vous sûr de vouloir supprimer ce webtoon ?\nCette action est irréversible.")) {
        // Si l'utilisateur confirme, on suit le lien
        window.location.href = lien;
    }
    // Si l'utilisateur annule, on ne fait rien
    return false;
}

// ===== FILTRES DE LA LISTE DES WEBTOONS =====
// Permet de filtrer les cartes par statut sans recharger la page
function filtrerWebtoons(statut) {
    // On récupère toutes les cartes de webtoon
    const cartes = document.querySelectorAll('.carte-webtoon');

    cartes.forEach(function(carte) {
        // Si le filtre est "tous" ou si la carte correspond au statut, on l'affiche
        // On utilise '' (vide) et non 'block' pour laisser la CSS grid gérer l'affichage
        if (statut === 'tous' || carte.dataset.statut === statut) {
            carte.style.display = '';
        } else {
            carte.style.display = 'none';
        }
    });

    // On met en surbrillance le bouton de filtre actif
    const boutons = document.querySelectorAll('.filtre-btn');
    boutons.forEach(function(bouton) {
        bouton.classList.remove('actif');
        if (bouton.dataset.filtre === statut) {
            bouton.classList.add('actif');
        }
    });
}

// ===== PRÉVISUALISATION DE L'IMAGE =====
// Affiche un aperçu de l'image quand l'utilisateur entre une URL
function previsualiserImage() {
    const urlInput = document.getElementById('image_url');
    const preview  = document.getElementById('apercu-image');

    if (!urlInput || !preview) return; // Si les éléments n'existent pas, on arrête

    const url = urlInput.value.trim();

    if (url !== '') {
        preview.src = url;
        preview.style.display = 'block';
        // Si l'image ne charge pas, on la cache
        preview.onerror = function() {
            preview.style.display = 'none';
        };
    } else {
        preview.style.display = 'none';
    }
}

// ===== INITIALISATION AU CHARGEMENT DE LA PAGE =====
document.addEventListener('DOMContentLoaded', function() {
    // On initialise la prévisualisation d'image si le champ existe
    const champImage = document.getElementById('image_url');
    if (champImage) {
        champImage.addEventListener('input', previsualiserImage);
        // Si une URL est déjà présente (ex: page de modification), on l'affiche
        previsualiserImage();
    }

    // On active le bouton "Tous" par défaut sur la page des webtoons
    const btnTous = document.querySelector('.filtre-btn[data-filtre="tous"]');
    if (btnTous) {
        btnTous.classList.add('actif');
    }
});
