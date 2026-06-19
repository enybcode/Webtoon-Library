<?php
// =============================================
// inscription.php - Page d'inscription
// =============================================

session_start();
include 'includes/config.php';
include 'includes/lang.php';

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
        $erreur = t('required_fields');
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
                "INSERT INTO utilisateurs (email, pseudo, mot_de_passe, langue) VALUES (?, ?, ?, 'en')"
            );
            $insert->execute([$email, $pseudo, $motDePasseHash]);

            $succes = "Compte cree avec succes ! Vous pouvez maintenant vous connecter.";
        }
    }
}

$titre_page = t('register');
include 'includes/header.php';
?>

<div class="carte-formulaire">
    <h1><?= htmlspecialchars(t('create_account')) ?></h1>

    <?php if ($erreur): ?>
        <div class="alerte alerte-erreur"><?= htmlspecialchars($erreur) ?></div>
    <?php endif; ?>

    <?php if ($succes): ?>
        <div class="alerte alerte-succes"><?= htmlspecialchars($succes) ?></div>
    <?php endif; ?>

    <form method="POST" action="inscription.php">
        <div class="groupe-champ">
            <label for="email"><?= htmlspecialchars(t('email')) ?></label>
            <input type="email" id="email" name="email"
                   placeholder="exemple@mail.com"
                   value="<?= htmlspecialchars($_POST['email'] ?? '') ?>"
                   required>
        </div>

        <div class="groupe-champ">
            <label for="pseudo"><?= htmlspecialchars(t('pseudo')) ?></label>
            <input type="text" id="pseudo" name="pseudo"
                   placeholder="VotreNom123"
                   value="<?= htmlspecialchars($_POST['pseudo'] ?? '') ?>"
                   required>
        </div>

        <div class="groupe-champ">
            <label for="mot_de_passe"><?= htmlspecialchars(t('password')) ?> <small>(min. 6)</small></label>
            <input type="password" id="mot_de_passe" name="mot_de_passe"
                   placeholder="********"
                   required>
        </div>

        <button type="submit" class="btn btn-vert btn-bloc"><?= htmlspecialchars(t('create_account')) ?></button>
    </form>

    <p class="lien-formulaire">
        <?= langueCourante() === 'fr' ? 'Déjà un compte ?' : 'Already have an account?' ?> <a href="connexion.php"><?= htmlspecialchars(t('login')) ?></a>
    </p>
</div>

<?php include 'includes/footer.php'; ?>
