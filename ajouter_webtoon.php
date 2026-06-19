<?php
// =============================================
// ajouter_webtoon.php - Formulaire d'ajout manuel
// =============================================

session_start();
include 'includes/config.php';
include 'includes/traductions.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: connexion.php');
    exit;
}

$erreur = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $titre = trim($_POST['titre'] ?? '');
    $auteur = trim($_POST['auteur'] ?? '');
    $genre = traduireGenres(trim($_POST['genre'] ?? ''));
    $desc = descriptionSelonLangue($_POST['description'] ?? '');
    $statutsValides = ['a_lire', 'en_cours', 'en_pause', 'termine', 'abandonne'];
    $statut = in_array($_POST['statut'] ?? '', $statutsValides) ? $_POST['statut'] : 'a_lire';
    $chapitre = max(0, (int)($_POST['chapitre_actuel'] ?? 0));
    $note = ($_POST['note'] ?? '') !== '' ? (int)$_POST['note'] : null;
    $imageUrl = trim($_POST['image_url'] ?? '');

    if ($note !== null && ($note < 0 || $note > 10)) {
        $note = null;
    }

    if ($titre === '') {
        $erreur = t('required_title');
    } else {
        $requete = $pdo->prepare(
            "INSERT INTO webtoons
             (id_utilisateur, titre, auteur, genre, description, statut, chapitre_actuel, note, image_url)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)"
        );
        $requete->execute([
            $_SESSION['user_id'],
            $titre,
            $auteur,
            $genre,
            $desc,
            $statut,
            $chapitre,
            $note,
            $imageUrl
        ]);

        header('Location: webtoons.php?ajout=ok');
        exit;
    }
}

$titre_page = t('add_webtoon');
include 'includes/header.php';
?>

<div class="carte-formulaire-large">
    <h1><?= htmlspecialchars(t('add_webtoon')) ?></h1>
    <p class="texte-page"><?= htmlspecialchars(t('manual_add_warning')) ?></p>

    <?php if ($erreur): ?>
        <div class="alerte alerte-erreur"><?= htmlspecialchars($erreur) ?></div>
    <?php endif; ?>

    <form method="POST" action="ajouter_webtoon.php">
        <div class="groupe-champ">
            <label for="titre"><?= htmlspecialchars(t('title')) ?> <span style="color:red">*</span></label>
            <input type="text" id="titre" name="titre"
                   placeholder="Solo Leveling"
                   value="<?= htmlspecialchars($_POST['titre'] ?? '') ?>"
                   required>
        </div>

        <div class="grille-2-colonnes">
            <div class="groupe-champ">
                <label for="auteur"><?= htmlspecialchars(t('author')) ?></label>
                <input type="text" id="auteur" name="auteur"
                       placeholder="Chugong"
                       value="<?= htmlspecialchars($_POST['auteur'] ?? '') ?>">
            </div>

            <div class="groupe-champ">
                <label for="genre"><?= htmlspecialchars(t('genre')) ?></label>
                <input type="text" id="genre" name="genre"
                       placeholder="Action, Fantasy"
                       value="<?= htmlspecialchars($_POST['genre'] ?? '') ?>">
            </div>
        </div>

        <div class="groupe-champ">
            <label for="description"><?= htmlspecialchars(t('description')) ?></label>
            <textarea id="description" name="description"
                      placeholder="<?= htmlspecialchars(langueCourante() === 'fr' ? "Resume de l'histoire..." : 'Story summary...') ?>"><?= htmlspecialchars($_POST['description'] ?? '') ?></textarea>
        </div>

        <div class="grille-2-colonnes">
            <div class="groupe-champ">
                <label for="statut"><?= htmlspecialchars(t('status')) ?></label>
                <select id="statut" name="statut">
                    <option value="a_lire" <?= (($_POST['statut'] ?? '') === 'a_lire') ? 'selected' : '' ?>><?= htmlspecialchars(t('to_read')) ?></option>
                    <option value="en_cours" <?= (($_POST['statut'] ?? '') === 'en_cours') ? 'selected' : '' ?>><?= htmlspecialchars(t('reading')) ?></option>
                    <option value="en_pause" <?= (($_POST['statut'] ?? '') === 'en_pause') ? 'selected' : '' ?>><?= htmlspecialchars(t('paused')) ?></option>
                    <option value="termine" <?= (($_POST['statut'] ?? '') === 'termine') ? 'selected' : '' ?>><?= htmlspecialchars(t('finished')) ?></option>
                    <option value="abandonne" <?= (($_POST['statut'] ?? '') === 'abandonne') ? 'selected' : '' ?>><?= htmlspecialchars(t('dropped')) ?></option>
                </select>
            </div>

            <div class="groupe-champ">
                <label for="chapitre_actuel"><?= htmlspecialchars(t('chapter_current')) ?></label>
                <input type="number" id="chapitre_actuel" name="chapitre_actuel"
                       min="0" value="<?= (int)($_POST['chapitre_actuel'] ?? 0) ?>">
            </div>
        </div>

        <div class="groupe-champ">
            <label for="note"><?= htmlspecialchars(t('score')) ?> / 10</label>
            <select id="note" name="note">
                <option value=""><?= htmlspecialchars(t('not_rated')) ?></option>
                <?php for ($i = 0; $i <= 10; $i++): ?>
                    <option value="<?= $i ?>"
                        <?= (isset($_POST['note']) && (string)$_POST['note'] === (string)$i) ? 'selected' : '' ?>>
                        <?= $i ?>/10
                    </option>
                <?php endfor; ?>
            </select>
        </div>

        <div class="groupe-champ">
            <label for="image_url"><?= htmlspecialchars(t('image_url')) ?></label>
            <input type="url" id="image_url" name="image_url"
                   placeholder="https://example.com/image.jpg"
                   value="<?= htmlspecialchars($_POST['image_url'] ?? '') ?>">
            <img id="apercu-image"
                 style="display:none; margin-top:0.8rem; border-radius:8px; max-height:200px;"
                 alt="<?= htmlspecialchars(t('image_url')) ?>">
        </div>

        <div style="display:flex; gap:1rem; margin-top:0.5rem;">
            <button type="submit" class="btn btn-vert"><?= htmlspecialchars(t('add_webtoon')) ?></button>
            <a href="webtoons.php" class="btn btn-gris"><?= htmlspecialchars(t('cancel')) ?></a>
        </div>
    </form>
</div>

<?php include 'includes/footer.php'; ?>
