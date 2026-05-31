<?php
// =============================================
// supprimer_webtoon.php — Suppression d'un webtoon
// Ce fichier n'affiche pas de page, il effectue
// l'action et redirige immédiatement.
// =============================================

session_start();
include 'includes/config.php';

// Protection : l'utilisateur doit être connecté
if (!isset($_SESSION['user_id'])) {
    header('Location: connexion.php');
    exit;
}

$userId = $_SESSION['user_id'];
$id = (int)($_GET['id'] ?? 0);

// Vérification : l'ID doit être valide
if ($id === 0) {
    header('Location: webtoons.php');
    exit;
}

// On supprime le webtoon SEULEMENT si il appartient à l'utilisateur connecté
// Ceci empêche un utilisateur de supprimer le webtoon d'un autre
$requete = $pdo->prepare("DELETE FROM webtoons WHERE id = ? AND id_utilisateur = ?");
$requete->execute([$id, $userId]);

// On redirige vers la liste
header('Location: webtoons.php?supprime=ok');
exit;
?>
