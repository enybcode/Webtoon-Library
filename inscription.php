<?php
// =============================================
// inscription.php — Page d'inscription
// =============================================

session_start();            // DOIT être en premier, avant tout include
include 'includes/config.php'; // Connexion BDD

// Si l'utilisateur est déjà connecté, on le redirige
if (isset($_SESSION['user_id'])) {
    header('Location: dashboard.php');
    exit;
}

$erreur  = ''; // Message d'erreur
$succes  = ''; // Message de succès

// ===== TRAITEMENT DU FORMULAIRE =====
// On vérifie si le formulaire a été soumis (méthode POST)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // On récupère et nettoie les données du formulaire
    $email    = trim($_POST['email'] ?? '');
    $pseudo   = trim($_POST['pseudo'] ?? '');
    $password = $_POST['mot_de_passe'] ?? '';

    // --- Vérifications ---
    if (empty($email) || empty($pseudo) || empty($password)) {
        $erreur = "Tous les champs sont obligatoires.";

    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $erreur = "L'adresse email n'est pas valide.";

    } elseif (strlen($pseudo) < 3 || strlen($pseudo) > 50) {
        $erreur = "Le pseudo doit contenir entre 3 et 50 caractères.";

    } elseif (strlen($password) < 6) {
        $erreur = "Le mot de passe doit contenir au moins 6 caractères.";

    } else {
        // On vérifie si l'email ou le pseudo est déjà utilisé
        $requete = $pdo->prepare("SELECT id FROM utilisateurs WHERE email = ? OR pseudo = ?");
        $requete->execute([$email, $pseudo]);

        if ($requete->fetch()) {
            $erreur = "Cet email ou ce pseudo est déjà utilisé.";
        } else {
            // On sécurise le mot de passe avec password_hash
            $motDePasseHash = password_hash($password, PASSWORD_DEFAULT);

            // On insère le nouvel utilisateur dans la base
            $insert = $pdo->prepare(
                "INSERT INTO utilisateurs (email, pseudo, mot_de_passe) VALUES (?, ?, ?)"
            );
            $insert->execute([$email, $pseudo, $motDePasseHash]);

            $succes = "Compte créé avec succès ! Vous pouvez maintenant vous connecter.";
        }
    }
}

$titre_page = "Inscription";
include 'includes/header.php';
?>

<div class="carte-formulaire">
    <h1>✏️ Créer un compte</h1>

    <!-- Affichage des messages -->
    <?php if ($erreur): ?>
        <div class="alerte alerte-erreur"><?= htmlspecialchars($erreur) ?></div>
    <?php endif; ?>

    <?php if ($succes): ?>
        <div class="alerte alerte-succes"><?= htmlspecialchars($succes) ?></div>
    <?php endif; ?>

    <!-- Formulaire d'inscription -->
    <form method="POST" action="inscription.php">

        <div class="groupe-champ">
            <label for="email">Adresse email</label>
            <input type="email" id="email" name="email"
                   placeholder="exemple@mail.com"
                   value="<?= htmlspecialchars($_POST['email'] ?? '') ?>"
                   required>
        </div>

        <div class="groupe-champ">
            <label for="pseudo">Pseudo</label>
            <input type="text" id="pseudo" name="pseudo"
                   placeholder="VotreNom123"
                   value="<?= htmlspecialchars($_POST['pseudo'] ?? '') ?>"
                   required>
        </div>

        <div class="groupe-champ">
            <label for="mot_de_passe">Mot de passe <small>(min. 6 caractères)</small></label>
            <input type="password" id="mot_de_passe" name="mot_de_passe"
                   placeholder="••••••••"
                   required>
        </div>

        <button type="submit" class="btn btn-vert btn-bloc">Créer mon compte</button>
    </form>

    <!-- Lien vers la connexion -->
    <p class="lien-formulaire">
        Déjà un compte ? <a href="connexion.php">Se connecter</a>
    </p>
</div>

<?php include 'includes/footer.php'; ?>
