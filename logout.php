<?php
// =============================================
// logout.php — Déconnexion de l'utilisateur
// =============================================

session_start();

// On détruit toute la session (supprime toutes les données de session)
session_destroy();

// On redirige vers la page d'accueil
header('Location: index.php');
exit;
?>
