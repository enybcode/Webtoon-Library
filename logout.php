<?php
// =============================================
// logout.php — Déconnexion de l'utilisateur
// =============================================

session_start();

// On vide toutes les variables de session
session_unset();

// On détruit la session complètement
session_destroy();

// On redirige vers la page d'accueil
header('Location: index.php');
exit;
?>
