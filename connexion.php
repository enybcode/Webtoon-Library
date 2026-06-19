<?php
// =============================================
// connexion.php - Page de connexion
// =============================================

session_start();
include 'includes/config.php';
include 'includes/lang.php';
include 'includes/security.php';

if (isset($_SESSION['user_id'])) {
    header('Location: dashboard.php');
    exit;
}

$erreur = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifierCsrf()) {
        refuserRequeteInvalide();
    }

    $pseudo   = trim($_POST['pseudo'] ?? '');
    $password = $_POST['mot_de_passe'] ?? '';

    if (empty($pseudo) || empty($password)) {
        $erreur = t('required_fields');
    } else {
        $requete = $pdo->prepare("SELECT * FROM utilisateurs WHERE pseudo = ?");
        $requete->execute([$pseudo]);
        $utilisateur = $requete->fetch();

        if ($utilisateur && password_verify($password, $utilisateur['mot_de_passe'])) {
            session_regenerate_id(true);
            $_SESSION['user_id'] = $utilisateur['id'];
            $_SESSION['user_pseudo'] = $utilisateur['pseudo'];
            $_SESSION['langue'] = $utilisateur['langue'] ?? 'en';
            $_SESSION['is_admin'] = !empty($utilisateur['is_admin']);

            header('Location: dashboard.php');
            exit;
        } else {
            $erreur = t('bad_login');
        }
    }
}

$titre_page = t('login');
include 'includes/header.php';
?>

<div class="carte-formulaire">
    <h1><?= htmlspecialchars(t('login')) ?></h1>

    <?php if ($erreur): ?>
        <div class="alerte alerte-erreur"><?= htmlspecialchars($erreur) ?></div>
    <?php endif; ?>

    <form method="POST" action="connexion.php">
        <?= champCsrf() ?>
        <div class="groupe-champ">
            <label for="pseudo"><?= htmlspecialchars(t('pseudo')) ?></label>
            <input type="text" id="pseudo" name="pseudo"
                   placeholder="VotreNom123"
                   value="<?= htmlspecialchars($_POST['pseudo'] ?? '') ?>"
                   required>
        </div>

        <div class="groupe-champ">
            <label for="mot_de_passe"><?= htmlspecialchars(t('password')) ?></label>
            <input type="password" id="mot_de_passe" name="mot_de_passe"
                   placeholder="********"
                   required>
        </div>

        <button type="submit" class="btn btn-vert btn-bloc"><?= htmlspecialchars(t('login')) ?></button>
    </form>

    <p class="lien-formulaire">
        <?= langueCourante() === 'fr' ? 'Pas encore inscrit ?' : 'No account yet?' ?> <a href="inscription.php"><?= htmlspecialchars(t('create_account')) ?></a>
    </p>
</div>

<?php include 'includes/footer.php'; ?>
