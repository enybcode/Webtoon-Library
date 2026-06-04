<?php
// =============================================
// modifier_webtoon.php — Modification d'un webtoon
// =============================================

session_start();
include 'includes/config.php';

// Protection de la page
if (!isset($_SESSION['user_id'])) {
    header('Location: connexion.php');
    exit;
}

$userId = $_SESSION['user_id'];
$erreur = '';

// ===== RÉCUPÉRATION DU WEBTOON À MODIFIER =====
// On récupère l'ID depuis l'URL (?id=X)
$id = (int)($_GET['id'] ?? 0);

if ($id === 0) {
    header('Location: webtoons.php');
    exit;
}

// On cherche le webtoon dans la base
// IMPORTANT : on vérifie que le webtoon appartient bien à l'utilisateur connecté
$requete = $pdo->prepare("SELECT * FROM webtoons WHERE id = ? AND id_utilisateur = ?");
$requete->execute([$id, $userId]);
$wt = $requete->fetch();

// Si le webtoon n'existe pas ou ne lui appartient pas, on redirige
if (!$wt) {
    header('Location: webtoons.php');
    exit;
}

// ===== TRAITEMENT DU FORMULAIRE DE MODIFICATION =====
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $titre    = trim($_POST['titre'] ?? '');
    $auteur   = trim($_POST['auteur'] ?? '');
    $genre    = trim($_POST['genre'] ?? '');
    $desc     = trim($_POST['description'] ?? '');
    // On valide que le statut est bien une des 3 valeurs autorisées
    $statutsValides = ['a_lire', 'en_cours', 'termine'];
    $statut   = in_array($_POST['statut'] ?? '', $statutsValides) ? $_POST['statut'] : 'a_lire';
    $chapitre = (int)($_POST['chapitre_actuel'] ?? 0);
    $note     = ($_POST['note'] ?? '') !== '' ? (int)$_POST['note'] : null;
    // Sécurité : on s'assure que la note est bien entre 0 et 10
    if ($note !== null && ($note < 0 || $note > 10)) $note = null;
    $imageUrl = trim($_POST['image_url'] ?? '');

    if (empty($titre)) {
        $erreur = "Le titre est obligatoire.";
    } else {
        // On met à jour le webtoon
        // On s'assure encore que id_utilisateur correspond (sécurité)
        $update = $pdo->prepare(
            "UPDATE webtoons
             SET titre=?, auteur=?, genre=?, description=?, statut=?,
                 chapitre_actuel=?, note=?, image_url=?
             WHERE id=? AND id_utilisateur=?"
        );
        $update->execute([
            $titre, $auteur, $genre, $desc,
            $statut, $chapitre, $note, $imageUrl,
            $id, $userId
        ]);

        header('Location: webtoons.php?modif=ok');
        exit;
    }

    // Si erreur, on remet uniquement les champs du formulaire dans $wt
    // (on ne fait PAS array_merge($wt, $_POST) pour éviter d'écraser des champs système)
    $wt['titre']           = $titre;
    $wt['auteur']          = $auteur;
    $wt['genre']           = $genre;
    $wt['description']     = $desc;
    $wt['statut']          = $statut;
    $wt['chapitre_actuel'] = $chapitre;
    $wt['note']            = $note;
    $wt['image_url']       = $imageUrl;
}

$titre_page = "Modifier un webtoon";
include 'includes/header.php';
?>

<div class="carte-formulaire-large">
    <h1>Modifier un webtoon</h1>

    <?php if ($erreur): ?>
        <div class="alerte alerte-erreur"><?= htmlspecialchars($erreur) ?></div>
    <?php endif; ?>

    <form method="POST" action="modifier_webtoon.php?id=<?= $id ?>">
        <!-- Sécurité : on passe aussi l'id en champ caché au cas où l'URL serait modifiée -->
        <input type="hidden" name="id" value="<?= $id ?>">

        <div class="groupe-champ">
            <label for="titre">Titre <span style="color:red">*</span></label>
            <input type="text" id="titre" name="titre"
                   value="<?= htmlspecialchars($wt['titre']) ?>" required>
        </div>

        <div class="grille-2-colonnes">
            <div class="groupe-champ">
                <label for="auteur">Auteur</label>
                <input type="text" id="auteur" name="auteur"
                       value="<?= htmlspecialchars($wt['auteur'] ?? '') ?>">
            </div>

            <div class="groupe-champ">
                <label for="genre">Genre</label>
                <input type="text" id="genre" name="genre"
                       value="<?= htmlspecialchars($wt['genre'] ?? '') ?>">
            </div>
        </div>

        <div class="groupe-champ">
            <label for="description">Description</label>
            <textarea id="description" name="description"><?= htmlspecialchars($wt['description'] ?? '') ?></textarea>
        </div>

        <div class="grille-2-colonnes">
            <div class="groupe-champ">
                <label for="statut">Statut de lecture</label>
                <select id="statut" name="statut">
                    <option value="a_lire"   <?= $wt['statut'] === 'a_lire'   ? 'selected' : '' ?>>À lire</option>
                    <option value="en_cours" <?= $wt['statut'] === 'en_cours' ? 'selected' : '' ?>>En cours</option>
                    <option value="termine"  <?= $wt['statut'] === 'termine'  ? 'selected' : '' ?>>Terminé</option>
                </select>
            </div>

            <div class="groupe-champ">
                <label for="chapitre_actuel">Chapitre actuel</label>
                <input type="number" id="chapitre_actuel" name="chapitre_actuel"
                       min="0" value="<?= (int)$wt['chapitre_actuel'] ?>">
            </div>
        </div>

        <div class="groupe-champ">
            <label for="note">Note (0 à 10)</label>
            <select id="note" name="note">
                <option value="">-- Pas encore notée --</option>
                <?php for ($i = 0; $i <= 10; $i++): ?>
                    <option value="<?= $i ?>"
                        <?= (!is_null($wt['note']) && (int)$wt['note'] === $i) ? 'selected' : '' ?>>
                        <?= $i ?>/10
                    </option>
                <?php endfor; ?>
            </select>
        </div>

        <div class="groupe-champ">
            <label for="image_url">URL de l'image</label>
            <input type="url" id="image_url" name="image_url"
                   value="<?= htmlspecialchars($wt['image_url'] ?? '') ?>"
                   placeholder="https://exemple.com/image.jpg">
            <img id="apercu-image"
                 style="display:none; margin-top:0.8rem; border-radius:8px; max-height:200px;"
                 alt="Aperçu">
        </div>

        <div style="display:flex; gap:1rem; margin-top:0.5rem;">
            <button type="submit" class="btn btn-vert">
                <img src="<?= $base ?>/assets/img/icon-save.svg" alt="" class="action-icon"> Enregistrer
            </button>
            <a href="webtoons.php" class="btn btn-gris">Annuler</a>
        </div>
    </form>
</div>

<?php include 'includes/footer.php'; ?>
