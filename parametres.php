<?php
// =============================================
// parametres.php - Preferences utilisateur
// =============================================

session_start();
include 'includes/config.php';
include 'includes/lang.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: connexion.php');
    exit;
}

if (!isset($_SESSION['inclure_adulte'])) {
    $_SESSION['inclure_adulte'] = false;
}

$succes = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $_SESSION['inclure_adulte'] = isset($_POST['inclure_adulte']);
    $langue = in_array($_POST['langue'] ?? 'en', ['en', 'fr']) ? $_POST['langue'] : 'en';
    $_SESSION['langue'] = $langue;

    $updateLangue = $pdo->prepare("UPDATE utilisateurs SET langue = ? WHERE id = ?");
    $updateLangue->execute([$langue, $_SESSION['user_id']]);

    $succes = t('settings_saved');
}

$titre_page = t('settings');
include 'includes/header.php';
?>

<div class="entete-page">
    <h1 class="page-titre"><?= htmlspecialchars(t('settings')) ?></h1>
</div>

<?php if ($succes): ?>
    <div class="alerte alerte-succes"><?= htmlspecialchars($succes) ?></div>
<?php endif; ?>

<div class="grille-parametres">
    <section class="carte-parametres">
        <h2><?= htmlspecialchars(langueCourante() === 'fr' ? 'Compte' : 'Account') ?></h2>
        <p>
            <?= htmlspecialchars(langueCourante() === 'fr' ? 'Vous etes connecte avec le pseudo' : 'You are logged in as') ?>
            <?= htmlspecialchars($_SESSION['user_pseudo'] ?? '') ?>.
        </p>
        <a href="logout.php" class="btn btn-rouge-plein"><?= htmlspecialchars(t('logout')) ?></a>
    </section>

    <section class="carte-parametres">
        <h2><?= htmlspecialchars(t('language')) ?></h2>
        <p><?= htmlspecialchars(langueCourante() === 'fr' ? "Choisissez la langue d'affichage du site." : 'Choose the display language.') ?></p>

        <form method="POST" action="parametres.php" class="form-parametres">
            <div class="groupe-champ">
                <label for="langue"><?= htmlspecialchars(t('language')) ?></label>
                <select id="langue" name="langue">
                    <option value="en" <?= langueCourante() === 'en' ? 'selected' : '' ?>>English</option>
                    <option value="fr" <?= langueCourante() === 'fr' ? 'selected' : '' ?>>Francais</option>
                </select>
            </div>

            <label class="toggle-ligne">
                <input type="checkbox" name="inclure_adulte" value="1" <?= !empty($_SESSION['inclure_adulte']) ? 'checked' : '' ?>>
                <span>
                    <strong><?= htmlspecialchars(t('adult_content')) ?></strong>
                    <small>
                        <?= htmlspecialchars(langueCourante() === 'fr'
                            ? 'Desactive par defaut. Ce choix reste en session.'
                            : 'Disabled by default. This choice stays in session.') ?>
                    </small>
                </span>
            </label>

            <button type="submit" class="btn btn-vert"><?= htmlspecialchars(t('save_settings')) ?></button>
        </form>
    </section>
</div>

<?php include 'includes/footer.php'; ?>
