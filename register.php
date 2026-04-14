<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Inscription</title>
</head>
<body>
    <h1>Inscription</h1>

    <form action="traitement_register.php" method="POST">
        <label>Email :</label><br>
        <input type="email" name="email" required><br><br>

        <label>Pseudo :</label><br>
        <input type="text" name="pseudo" required><br><br>

        <label>Mot de passe :</label><br>
        <input type="password" name="mot_de_passe" required><br><br>

        <button type="submit">S'inscrire</button>
    </form>

    <p>Déjà un compte ? <a href="login.php">Se connecter</a></p>
</body>
</html>