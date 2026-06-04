<?php
// =============================================
// ajouter_webtoon.php — Formulaire d'ajout
// =============================================

session_start();
include 'includes/config.php';

// Protection de la page
if (!isset($_SESSION['user_id'])) {
    header('Location: connexion.php');
    exit;
}

$erreur = '';
$succes = '';

// ===== TRAITEMENT DU FORMULAIRE =====
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // On récupère toutes les données du formulaire
    $titre     = trim($_POST['titre'] ?? '');
    $auteur    = trim($_POST['auteur'] ?? '');
    $genre     = trim($_POST['genre'] ?? '');
    $desc      = trim($_POST['description'] ?? '');
    // On valide que le statut est bien une des 3 valeurs autorisées
    $statutsValides = ['a_lire', 'en_cours', 'termine'];
    $statut = in_array($_POST['statut'] ?? '', $statutsValides) ? $_POST['statut'] : 'a_lire';
    $chapitre  = (int)($_POST['chapitre_actuel'] ?? 0);
    $note      = ($_POST['note'] ?? '') !== '' ? (int)$_POST['note'] : null;
    // Sécurité : on s'assure que la note est bien entre 0 et 10
    if ($note !== null && ($note < 0 || $note > 10)) $note = null;
    $imageUrl  = trim($_POST['image_url'] ?? '');

    // Vérification : le titre est obligatoire
    if (empty($titre)) {
        $erreur = "Le titre est obligatoire.";
    } else {
        // On insère le webtoon dans la base
        $requete = $pdo->prepare(
            "INSERT INTO webtoons
             (id_utilisateur, titre, auteur, genre, description, statut, chapitre_actuel, note, image_url)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)"
        );
        $requete->execute([
            $_SESSION['user_id'],
            $titre, $auteur, $genre, $desc,
            $statut, $chapitre, $note, $imageUrl
        ]);

        // On redirige vers la liste après l'ajout
        header('Location: webtoons.php?ajout=ok');
        exit;
    }
}

$titre_page = "Ajouter un webtoon";
include 'includes/header.php';
?>

<div class="carte-formulaire-large">
    <h1>Ajouter un webtoon</h1>

    <?php if ($erreur): ?>
        <div class="alerte alerte-erreur"><?= htmlspecialchars($erreur) ?></div>
    <?php endif; ?>

    <form method="POST" action="ajouter_webtoon.php">

        <!-- Titre (obligatoire) -->
        <div class="groupe-champ">
            <label for="titre">Titre <span style="color:red">*</span></label>
            <input type="text" id="titre" name="titre"
                   placeholder="Ex : Solo Leveling"
                   value="<?= htmlspecialchars($_POST['titre'] ?? '') ?>"
                   required>
        </div>

        <!-- Auteur et Genre côte à côte -->
        <div class="grille-2-colonnes">
            <div class="groupe-champ">
                <label for="auteur">Auteur</label>
                <input type="text" id="auteur" name="auteur"
                       placeholder="Ex : Chugong"
                       value="<?= htmlspecialchars($_POST['auteur'] ?? '') ?>">
            </div>

            <div class="groupe-champ">
                <label for="genre">Genre</label>
                <input type="text" id="genre" name="genre"
                       placeholder="Ex : Action, Fantasy"
                       value="<?= htmlspecialchars($_POST['genre'] ?? '') ?>">
            </div>
        </div>

        <!-- Description -->
        <div class="groupe-champ">
            <label for="description">Description</label>
            <textarea id="description" name="description"
                      placeholder="Résumé de l'histoire..."><?= htmlspecialchars($_POST['description'] ?? '') ?></textarea>
        </div>

        <!-- Statut et Chapitre côte à côte -->
        <div class="grille-2-colonnes">
            <div class="groupe-champ">
                <label for="statut">Statut de lecture</label>
                <select id="statut" name="statut">
                    <option value="a_lire"   <?= (($_POST['statut'] ?? '') === 'a_lire')   ? 'selected' : '' ?>>À lire</option>
                    <option value="en_cours" <?= (($_POST['statut'] ?? '') === 'en_cours') ? 'selected' : '' ?>>En cours</option>
                    <option value="termine"  <?= (($_POST['statut'] ?? '') === 'termine')  ? 'selected' : '' ?>>Terminé</option>
                </select>
            </div>

            <div class="groupe-champ">
                <label for="chapitre_actuel">Chapitre actuel</label>
                <input type="number" id="chapitre_actuel" name="chapitre_actuel"
                       min="0" value="<?= (int)($_POST['chapitre_actuel'] ?? 0) ?>">
            </div>
        </div>

        <!-- Note -->
        <div class="groupe-champ">
            <label for="note">Note (0 à 10)</label>
            <select id="note" name="note">
                <option value="">-- Pas encore notée --</option>
                <?php for ($i = 0; $i <= 10; $i++): ?>
                    <option value="<?= $i ?>"
                        <?= (isset($_POST['note']) && (string)$_POST['note'] === (string)$i) ? 'selected' : '' ?>>
                        <?= $i ?>/10
                    </option>
                <?php endfor; ?>
            </select>
        </div>

        <!-- URL de l'image -->
        <div class="groupe-champ">
            <label for="image_url">URL de l'image (optionnel)</label>
            <input type="url" id="image_url" name="image_url"
                   placeholder="https://exemple.com/image.jpg"
                   value="<?= htmlspecialchars($_POST['image_url'] ?? '') ?>">
            <!-- Aperçu de l'image (géré par script.js) -->
            <img id="apercu-image"
                 style="display:none; margin-top:0.8rem; border-radius:8px; max-height:200px;"
                 alt="Aperçu de l'image">
        </div>

        <!-- Boutons -->
        <div style="display:flex; gap:1rem; margin-top:0.5rem;">
            <button type="submit" class="btn btn-vert">Ajouter le webtoon</button>
            <a href="webtoons.php" class="btn btn-gris">Annuler</a>
        </div>
    </form>
</div>

<?php include 'includes/footer.php'; ?>
