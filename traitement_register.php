<?php
require_once("config.php");

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = trim($_POST["email"]);
    $pseudo = trim($_POST["pseudo"]);
    $mot_de_passe = trim($_POST["mot_de_passe"]);

    if (!empty($email) && !empty($pseudo) && !empty($mot_de_passe)) {
        $verif = $pdo->prepare("SELECT * FROM utilisateur WHERE email = ? OR pseudo = ?");
        $verif->execute([$email, $pseudo]);

        if ($verif->rowCount() > 0) {
            echo "Email ou pseudo déjà utilisé.";
        } else {
            $mot_de_passe_hash = password_hash($mot_de_passe, PASSWORD_DEFAULT);

            $sql = $pdo->prepare("INSERT INTO utilisateur (email, pseudo, mot_de_passe) VALUES (?, ?, ?)");
            $sql->execute([$email, $pseudo, $mot_de_passe_hash]);

            echo "Inscription réussie. <a href='login.php'>Se connecter</a>";
        }
    } else {
        echo "Tous les champs doivent être remplis.";
    }
} else {
    echo "Accès interdit.";
}
?>