<?php
// =============================================
// connexion.php — Page de connexion
// =============================================

session_start();            // DOIT être en premier, avant tout include
include 'includes/config.php';

// Si l'utilisateur est déjà connecté, on le redirige
if (isset($_SESSION['user_id'])) {
    header('Location: dashboard.php');
    exit;
}

$erreur = '';

// ===== TRAITEMENT DU FORMULAIRE =====
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $pseudo   = trim($_POST['pseudo'] ?? '');
    $password = $_POST['mot_de_passe'] ?? '';

    if (empty($pseudo) || empty($password)) {
        $erreur = "Veuillez remplir tous les champs.";
    } else {
        // On cherche l'utilisateur par son pseudo
        $requete = $pdo->prepare("SELECT * FROM utilisateurs WHERE pseudo = ?");
        $requete->execute([$pseudo]);
        $utilisateur = $requete->fetch();

        // On vérifie si l'utilisateur existe ET si le mot de passe est correct
        if ($utilisateur && password_verify($password, $utilisateur['mot_de_passe'])) {
            // Connexion réussie : on stocke les infos en session
            $_SESSION['user_id']    = $utilisateur['id'];
            $_SESSION['user_pseudo'] = $utilisateur['pseudo'];

            // On redirige vers le dashboard
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
    <h1>🔐 Connexion</h1>

    <!-- Affichage de l'erreur si nécessaire -->
    <?php if ($erreur): ?>
        <div class="alerte alerte-erreur"><?= htmlspecialchars($erreur) ?></div>
    <?php endif; ?>

    <!-- Formulaire de connexion -->
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
                   placeholder="••••••••"
                   required>
        </div>

        <button type="submit" class="btn btn-vert btn-bloc">Se connecter</button>
    </form>

    <!-- Lien vers l'inscription -->
    <p class="lien-formulaire">
        Pas encore inscrit ? <a href="inscription.php">Créer un compte</a>
    </p>
</div>

<?php include 'includes/footer.php'; ?>
