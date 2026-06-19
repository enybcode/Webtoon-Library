<?php
// =============================================
// modifier_webtoon.php - Modification du suivi personnel
// =============================================

session_start();
include 'includes/config.php';
include 'includes/traductions.php';
include 'includes/security.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: connexion.php');
    exit;
}

$userId = $_SESSION['user_id'];
$id = (int)($_GET['id'] ?? 0);
$erreur = '';

if ($id <= 0) {
    header('Location: webtoons.php');
    exit;
}

$requete = $pdo->prepare("SELECT * FROM webtoons WHERE id = ? AND id_utilisateur = ?");
$requete->execute([$id, $userId]);
$wt = $requete->fetch();

if (!$wt) {
    header('Location: webtoons.php');
    exit;
}

$statutsValides = ['a_lire', 'en_cours', 'en_pause', 'termine', 'abandonne'];
$intentionsValides = ['continuer', 'hesite', 'arreter'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifierCsrf()) {
        refuserRequeteInvalide();
    }

    $statut = in_array($_POST['statut'] ?? '', $statutsValides) ? $_POST['statut'] : 'a_lire';
    $intention = in_array($_POST['intention'] ?? '', $intentionsValides) ? $_POST['intention'] : 'hesite';
    $chapitre = max(0, (int)($_POST['chapitre_actuel'] ?? 0));
    $note = ($_POST['note'] ?? '') !== '' ? (int)$_POST['note'] : null;
    $commentaire = trim($_POST['commentaire'] ?? '');

    if ($note !== null && ($note < 0 || $note > 10)) {
        $erreur = t('score_error');
    } else {
        $update = $pdo->prepare(
            "UPDATE webtoons
             SET statut = ?, chapitre_actuel = ?, note = ?, commentaire = ?, intention = ?,
                 date_modification = CURRENT_TIMESTAMP
             WHERE id = ? AND id_utilisateur = ?"
        );
        $update->execute([
            $statut,
            $chapitre,
            $note,
            $commentaire !== '' ? $commentaire : null,
            $intention,
            $id,
            $userId
        ]);

        header('Location: webtoons.php?modif=ok');
        exit;
    }

    $wt['statut'] = $statut;
    $wt['chapitre_actuel'] = $chapitre;
    $wt['note'] = $note;
    $wt['commentaire'] = $commentaire;
    $wt['intention'] = $intention;
}

$titre_page = t('edit_progress');
include 'includes/header.php';
?>

<div class="carte-formulaire-large">
    <h1><?= htmlspecialchars(t('edit_progress')) ?></h1>

    <div class="resume-suivi">
        <?php if (!empty($wt['image_url'])): ?>
            <img src="<?= htmlspecialchars($wt['image_url']) ?>" alt="<?= htmlspecialchars($wt['titre']) ?>">
        <?php endif; ?>
        <div>
            <h2><?= htmlspecialchars($wt['titre']) ?></h2>
            <?php if (!empty($wt['genre'])): ?>
                <p><?= htmlspecialchars($wt['genre']) ?></p>
            <?php endif; ?>
        </div>
    </div>

    <?php if ($erreur): ?>
        <div class="alerte alerte-erreur"><?= htmlspecialchars($erreur) ?></div>
    <?php endif; ?>

    <form method="POST" action="modifier_webtoon.php?id=<?= (int)$id ?>">
        <?= champCsrf() ?>
        <div class="grille-2-colonnes">
            <div class="groupe-champ">
                <label for="statut"><?= htmlspecialchars(t('status')) ?></label>
                <select id="statut" name="statut">
                    <option value="a_lire" <?= $wt['statut'] === 'a_lire' ? 'selected' : '' ?>><?= htmlspecialchars(t('to_read')) ?></option>
                    <option value="en_cours" <?= $wt['statut'] === 'en_cours' ? 'selected' : '' ?>><?= htmlspecialchars(t('reading')) ?></option>
                    <option value="en_pause" <?= $wt['statut'] === 'en_pause' ? 'selected' : '' ?>><?= htmlspecialchars(t('paused')) ?></option>
                    <option value="termine" <?= $wt['statut'] === 'termine' ? 'selected' : '' ?>><?= htmlspecialchars(t('finished')) ?></option>
                    <option value="abandonne" <?= $wt['statut'] === 'abandonne' ? 'selected' : '' ?>><?= htmlspecialchars(t('dropped')) ?></option>
                </select>
            </div>

            <div class="groupe-champ">
                <label for="intention"><?= htmlspecialchars(t('intention')) ?></label>
                <select id="intention" name="intention">
                    <option value="continuer" <?= ($wt['intention'] ?? 'hesite') === 'continuer' ? 'selected' : '' ?>><?= htmlspecialchars(t('continue')) ?></option>
                    <option value="hesite" <?= ($wt['intention'] ?? 'hesite') === 'hesite' ? 'selected' : '' ?>><?= htmlspecialchars(t('unsure')) ?></option>
                    <option value="arreter" <?= ($wt['intention'] ?? 'hesite') === 'arreter' ? 'selected' : '' ?>><?= htmlspecialchars(t('stop')) ?></option>
                </select>
            </div>
        </div>

        <div class="grille-2-colonnes">
            <div class="groupe-champ">
                <label for="chapitre_actuel"><?= htmlspecialchars(t('chapter_current')) ?></label>
                <input type="number" id="chapitre_actuel" name="chapitre_actuel"
                       min="0" value="<?= (int)($wt['chapitre_actuel'] ?? 0) ?>">
            </div>

            <div class="groupe-champ">
                <label for="note"><?= htmlspecialchars(t('personal_score')) ?></label>
                <select id="note" name="note">
                    <option value=""><?= htmlspecialchars(t('not_rated')) ?></option>
                    <?php for ($i = 0; $i <= 10; $i++): ?>
                        <option value="<?= $i ?>" <?= (!is_null($wt['note']) && (int)$wt['note'] === $i) ? 'selected' : '' ?>>
                            <?= $i ?>/10
                        </option>
                    <?php endfor; ?>
                </select>
            </div>
        </div>

        <div class="groupe-champ">
            <label for="commentaire"><?= htmlspecialchars(t('comment')) ?></label>
            <textarea id="commentaire" name="commentaire" placeholder="<?= htmlspecialchars(langueCourante() === 'fr' ? "Ex : j'aime le rythme, mais je veux faire une pause..." : 'Example: I like the pacing, but I want to take a break...') ?>"><?= htmlspecialchars($wt['commentaire'] ?? '') ?></textarea>
        </div>

        <div class="actions-formulaire">
            <button type="submit" class="btn btn-vert">
                <img src="<?= $base ?>/assets/img/icon-save.svg" alt="" class="action-icon"> <?= htmlspecialchars(t('save')) ?>
            </button>
            <a href="webtoons.php" class="btn btn-gris"><?= htmlspecialchars(t('cancel')) ?></a>
            <?php if (!empty($wt['anilist_id'])): ?>
                <a href="detail_webtoon.php?id=<?= (int)$wt['anilist_id'] ?>" class="btn btn-gris"><?= htmlspecialchars(t('details')) ?></a>
            <?php endif; ?>
        </div>
    </form>
</div>

<?php include 'includes/footer.php'; ?>
