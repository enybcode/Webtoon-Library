<?php
// =============================================
// connexion.php - Page de connexion
// =============================================

session_start();
include 'includes/config.php';

if (isset($_SESSION['user_id'])) {
    header('Location: dashboard.php');
    exit;
}

$erreur = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $pseudo   = trim($_POST['pseudo'] ?? '');
    $password = $_POST['mot_de_passe'] ?? '';

    if (empty($pseudo) || empty($password)) {
        $erreur = "Veuillez remplir tous les champs.";
    } else {
        $requete = $pdo->prepare("SELECT * FROM utilisateurs WHERE pseudo = ?");
        $requete->execute([$pseudo]);
        $utilisateur = $requete->fetch();

        if ($utilisateur && password_verify($password, $utilisateur['mot_de_passe'])) {
            $_SESSION['user_id'] = $utilisateur['id'];
            $_SESSION['user_pseudo'] = $utilisateur['pseudo'];

            header('Location: dashboard.php');
            exit;
        } else {
            $erreur = "Pseudo ou mot de passe incorrect.";
        }
    }
}

$titre_page = "Connexion";
include 'includes/header.php';
?>

<div class="carte-formulaire">
    <h1>Connexion</h1>

    <?php if ($erreur): ?>
        <div class="alerte alerte-erreur"><?= htmlspecialchars($erreur) ?></div>
    <?php endif; ?>

    <form method="POST" action="connexion.php">
        <div class="groupe-champ">
            <label for="pseudo">Pseudo</label>
            <input type="text" id="pseudo" name="pseudo"
                   placeholder="VotreNom123"
                   value="<?= htmlspecialchars($_POST['pseudo'] ?? '') ?>"
                   required>
        </div>

        <div class="groupe-champ">
            <label for="mot_de_passe">Mot de passe</label>
            <input type="password" id="mot_de_passe" name="mot_de_passe"
                   placeholder="********"
                   required>
        </div>

        <button type="submit" class="btn btn-vert btn-bloc">Se connecter</button>
    </form>

    <p class="lien-formulaire">
        Pas encore inscrit ? <a href="inscription.php">Creer un compte</a>
    </p>
</div>

<?php include 'includes/footer.php'; ?>
