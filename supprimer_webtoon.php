<?php
// =============================================
// supprimer_webtoon.php — Suppression d'un webtoon
// =============================================

session_start();
include 'includes/config.php';

// Protection : l'utilisateur doit être connecté
if (!isset($_SESSION['user_id'])) {
    header('Location: connexion.php');
    exit;
}

$userId = $_SESSION['user_id'];
$id     = (int)($_GET['id'] ?? 0);

// Vérification : l'ID doit être valide
if ($id === 0) {
    header('Location: webtoons.php');
    exit;
}

// ── Protection CSRF simple ───────────────────────────────────────────────────
// On vérifie que la demande vient bien de notre propre site (pas d'un lien externe).
// Le HTTP_REFERER n'est pas infaillible mais suffit pour un projet BTS SIO.
$referer = $_SERVER['HTTP_REFERER'] ?? '';
$hote    = $_SERVER['HTTP_HOST']    ?? '';
if (!empty($referer) && !empty($hote) && strpos($referer, $hote) === false) {
    // La requête vient d'un autre site → on refuse
    header('Location: webtoons.php');
    exit;
}
// ────────────────────────────────────────────────────────────────────────────

// On supprime SEULEMENT si le webtoon appartient à l'utilisateur connecté
$requete = $pdo->prepare("DELETE FROM webtoons WHERE id = ? AND id_utilisateur = ?");
$requete->execute([$id, $userId]);

header('Location: webtoons.php?supprime=ok');
exit;
?>
