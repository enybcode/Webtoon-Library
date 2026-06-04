<?php
// =============================================
// parametres.php - Preferences utilisateur
// =============================================

session_start();

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
    $succes = "Preferences enregistrees.";
}

$titre_page = "Parametres";
include 'includes/header.php';
?>

<div class="entete-page">
    <h1 class="page-titre">Parametres</h1>
</div>

<?php if ($succes): ?>
    <div class="alerte alerte-succes"><?= htmlspecialchars($succes) ?></div>
<?php endif; ?>

<div class="grille-parametres">
    <section class="carte-parametres">
        <h2>Compte</h2>
        <p>Vous etes connecte avec le pseudo <?= htmlspecialchars($_SESSION['user_pseudo'] ?? '') ?>.</p>
        <a href="logout.php" class="btn btn-rouge-plein">Deconnexion</a>
    </section>

    <section class="carte-parametres">
        <h2>Preferences de recherche</h2>
        <p>Choisissez si les recherches AniList peuvent afficher des contenus adultes.</p>

        <form method="POST" action="parametres.php" class="form-parametres">
            <label class="toggle-ligne">
                <input type="checkbox" name="inclure_adulte" value="1" <?= !empty($_SESSION['inclure_adulte']) ? 'checked' : '' ?>>
                <span>
                    <strong>Activer les contenus +18</strong>
                    <small>Desactive par defaut. Ce choix reste en session.</small>
                </span>
            </label>

            <button type="submit" class="btn btn-vert">Enregistrer</button>
        </form>
    </section>
</div>

<?php include 'includes/footer.php'; ?>
