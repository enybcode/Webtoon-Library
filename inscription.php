<?php
// =============================================
// inscription.php - Page d'inscription
// =============================================

session_start();
include 'includes/config.php';

if (isset($_SESSION['user_id'])) {
    header('Location: dashboard.php');
    exit;
}

$erreur = '';
$succes = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $pseudo = trim($_POST['pseudo'] ?? '');
    $password = $_POST['mot_de_passe'] ?? '';

    if (empty($email) || empty($pseudo) || empty($password)) {
        $erreur = "Tous les champs sont obligatoires.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $erreur = "L'adresse email n'est pas valide.";
    } elseif (strlen($pseudo) < 3 || strlen($pseudo) > 50) {
        $erreur = "Le pseudo doit contenir entre 3 et 50 caracteres.";
    } elseif (strlen($password) < 6) {
        $erreur = "Le mot de passe doit contenir au moins 6 caracteres.";
    } else {
        $requete = $pdo->prepare("SELECT id FROM utilisateurs WHERE email = ? OR pseudo = ?");
        $requete->execute([$email, $pseudo]);

        if ($requete->fetch()) {
            $erreur = "Cet email ou ce pseudo est deja utilise.";
        } else {
            $motDePasseHash = password_hash($password, PASSWORD_DEFAULT);

            $insert = $pdo->prepare(
                "INSERT INTO utilisateurs (email, pseudo, mot_de_passe) VALUES (?, ?, ?)"
            );
            $insert->execute([$email, $pseudo, $motDePasseHash]);

            $succes = "Compte cree avec succes ! Vous pouvez maintenant vous connecter.";
        }
    }
}

$titre_page = "Inscription";
include 'includes/header.php';
?>

<div class="carte-formulaire">
    <h1>Creer un compte</h1>

    <?php if ($erreur): ?>
        <div class="alerte alerte-erreur"><?= htmlspecialchars($erreur) ?></div>
    <?php endif; ?>

    <?php if ($succes): ?>
        <div class="alerte alerte-succes"><?= htmlspecialchars($succes) ?></div>
    <?php endif; ?>

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
            <label for="mot_de_passe">Mot de passe <small>(min. 6 caracteres)</small></label>
            <input type="password" id="mot_de_passe" name="mot_de_passe"
                   placeholder="********"
                   required>
        </div>

        <button type="submit" class="btn btn-vert btn-bloc">Creer mon compte</button>
    </form>

    <p class="lien-formulaire">
        Deja un compte ? <a href="connexion.php">Se connecter</a>
    </p>
</div>

<?php include 'includes/footer.php'; ?>
