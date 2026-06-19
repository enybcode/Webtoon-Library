<?php
// =============================================
// supprimer_webtoon.php - Suppression d'un webtoon
// =============================================

session_start();
include 'includes/config.php';
include 'includes/security.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: connexion.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !verifierCsrf()) {
    refuserRequeteInvalide();
}

$userId = (int)$_SESSION['user_id'];
$id = (int)($_POST['id'] ?? 0);

if ($id <= 0) {
    header('Location: webtoons.php');
    exit;
}

// Suppression limitee au proprietaire connecte.
$requete = $pdo->prepare("DELETE FROM webtoons WHERE id = ? AND id_utilisateur = ?");
$requete->execute([$id, $userId]);

header('Location: webtoons.php?supprime=ok');
exit;
