<?php
// =============================================
// admin_db.php - Espace admin simple
// Objectif : gerer l'application sans SQL libre
// =============================================

session_start();
include 'includes/config.php';
include 'includes/lang.php';
include 'includes/security.php';

// ----- Securite de l'espace admin -----
$tokenFile = '/home/svasco/enzo/admin_token.txt';
$tokenAttendu = getenv('WEBTOON_ADMIN_TOKEN') ?: '';

if ($tokenAttendu === '' && file_exists($tokenFile)) {
    $tokenAttendu = trim(file_get_contents($tokenFile));
}

if (isset($_GET['token']) && $tokenAttendu !== '' && hash_equals($tokenAttendu, $_GET['token'])) {
    $_SESSION['admin_db_ok'] = true;
}

$adminConnecte = !empty($_SESSION['user_id']) && !empty($_SESSION['is_admin']);

if (empty($_SESSION['admin_db_ok']) && !$adminConnecte) {
    http_response_code(403);
    die('Acces refuse.');
}

if (isset($_GET['logout_admin'])) {
    unset($_SESSION['admin_db_ok']);
    header('Location: index.php');
    exit;
}

// ----- Fonctions utilitaires -----
function nomSql($nom)
{
    return '"' . str_replace('"', '""', $nom) . '"';
}

function tablesSqlite($pdo)
{
    $req = $pdo->query(
        "SELECT name FROM sqlite_master
         WHERE type = 'table'
         AND name NOT LIKE 'sqlite_%'
         ORDER BY name"
    );
    return $req->fetchAll(PDO::FETCH_COLUMN);
}

function colonnesSqlite($pdo, $table)
{
    $req = $pdo->query("PRAGMA table_info(" . nomSql($table) . ")");
    return $req->fetchAll();
}

function colonnePrimaire($colonnes)
{
    foreach ($colonnes as $colonne) {
        if ((int)$colonne['pk'] === 1) {
            return $colonne['name'];
        }
    }
    return $colonnes[0]['name'] ?? 'id';
}

function redirectionAdmin($onglet, $message = '')
{
    $url = 'admin_db.php?onglet=' . urlencode($onglet);
    if ($message !== '') {
        $url .= '&message=' . urlencode($message);
    }
    header('Location: ' . $url);
    exit;
}

// ----- Initialisation des categories si besoin -----
if ($pdo->getAttribute(PDO::ATTR_DRIVER_NAME) === 'sqlite') {
    initialiserBaseSqlite($pdo);
}

$onglet = $_GET['onglet'] ?? 'accueil';
$message = $_GET['message'] ?? '';
$erreur = '';

// ----- Actions admin -----
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifierCsrf()) {
        refuserRequeteInvalide();
    }

    $action = $_POST['action'] ?? '';

    if ($action === 'user_update') {
        $id = (int)($_POST['id'] ?? 0);
        $pseudo = trim($_POST['pseudo'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $langue = in_array($_POST['langue'] ?? 'en', ['en', 'fr']) ? $_POST['langue'] : 'en';
        $isAdmin = isset($_POST['is_admin']) ? 1 : 0;

        if ($id > 0 && $pseudo !== '' && $email !== '' && filter_var($email, FILTER_VALIDATE_EMAIL)) {
            if (!empty($_SESSION['user_id']) && $id === (int)$_SESSION['user_id'] && $isAdmin === 0) {
                $erreur = 'Impossible de retirer ton propre role admin.';
            } else {
                $req = $pdo->prepare(
                    "UPDATE utilisateurs
                     SET pseudo = ?, email = ?, langue = ?, is_admin = ?
                     WHERE id = ?"
                );
                $req->execute([$pseudo, $email, $langue, $isAdmin, $id]);
                redirectionAdmin('utilisateurs', 'Profil modifie.');
            }
        }
    }

    if ($action === 'user_password') {
        $id = (int)($_POST['id'] ?? 0);
        $password = $_POST['password'] ?? '';

        if ($id > 0 && strlen($password) >= 6) {
            $req = $pdo->prepare("UPDATE utilisateurs SET mot_de_passe = ? WHERE id = ?");
            $req->execute([password_hash($password, PASSWORD_DEFAULT), $id]);
            redirectionAdmin('utilisateurs', 'Mot de passe modifie.');
        } else {
            $erreur = 'Le mot de passe doit faire au moins 6 caracteres.';
        }
    }

    if ($action === 'category_update') {
        $id = (int)($_POST['id'] ?? 0);
        $nomAnilist = trim($_POST['nom_anilist'] ?? '');
        $labelFr = trim($_POST['label_fr'] ?? '');
        $labelEn = trim($_POST['label_en'] ?? '');
        $actif = isset($_POST['actif']) ? 1 : 0;
        $adulte = isset($_POST['adulte']) ? 1 : 0;
        $ordre = (int)($_POST['ordre_affichage'] ?? 0);

        if ($id > 0 && $nomAnilist !== '' && $labelFr !== '' && $labelEn !== '') {
            $req = $pdo->prepare(
                "UPDATE categories_admin
                 SET nom_anilist = ?, label_fr = ?, label_en = ?, actif = ?, adulte = ?, ordre_affichage = ?
                 WHERE id = ?"
            );
            $req->execute([$nomAnilist, $labelFr, $labelEn, $actif, $adulte, $ordre, $id]);
            redirectionAdmin('categories', 'Categorie modifiee.');
        }
    }

    if ($action === 'category_add') {
        $nomAnilist = trim($_POST['nom_anilist'] ?? '');
        $labelFr = trim($_POST['label_fr'] ?? '');
        $labelEn = trim($_POST['label_en'] ?? '');
        $ordre = (int)($_POST['ordre_affichage'] ?? 0);

        if ($nomAnilist !== '' && $labelFr !== '' && $labelEn !== '') {
            $req = $pdo->prepare(
                "INSERT INTO categories_admin
                 (nom_anilist, label_fr, label_en, actif, adulte, ordre_affichage)
                 VALUES (?, ?, ?, 1, 1, ?)"
            );
            $req->execute([$nomAnilist, $labelFr, $labelEn, $ordre]);
            redirectionAdmin('categories', 'Categorie ajoutee.');
        }
    }

    if ($action === 'category_delete') {
        $id = (int)($_POST['id'] ?? 0);
        if ($id > 0) {
            $req = $pdo->prepare("DELETE FROM categories_admin WHERE id = ?");
            $req->execute([$id]);
            redirectionAdmin('categories', 'Categorie supprimee.');
        }
    }

    if ($action === 'raw_update') {
        $table = $_POST['table'] ?? '';
        $tables = tablesSqlite($pdo);

        if (in_array($table, $tables, true)) {
            $colonnes = colonnesSqlite($pdo, $table);
            $pk = colonnePrimaire($colonnes);
            $id = $_POST['id'] ?? '';
            $sets = [];
            $valeurs = [];

            foreach ($colonnes as $colonne) {
                $nom = $colonne['name'];
                if ($nom === $pk) {
                    continue;
                }
                if (array_key_exists($nom, $_POST)) {
                    $sets[] = nomSql($nom) . ' = ?';
                    $valeurs[] = $_POST[$nom] === '' ? null : $_POST[$nom];
                }
            }

            if (!empty($sets)) {
                $valeurs[] = $id;
                $req = $pdo->prepare(
                    "UPDATE " . nomSql($table) .
                    " SET " . implode(', ', $sets) .
                    " WHERE " . nomSql($pk) . " = ?"
                );
                $req->execute($valeurs);
                redirectionAdmin('base', 'Ligne modifiee.');
            }
        }
    }
}

// ----- Donnees pour les onglets -----
$stats = [
    'utilisateurs' => (int)$pdo->query("SELECT COUNT(*) FROM utilisateurs")->fetchColumn(),
    'webtoons' => (int)$pdo->query("SELECT COUNT(*) FROM webtoons")->fetchColumn(),
    'categories' => (int)$pdo->query("SELECT COUNT(*) FROM categories_admin")->fetchColumn(),
    'traductions' => (int)$pdo->query("SELECT COUNT(*) FROM traductions_cache")->fetchColumn()
];

$titre_page = 'Administration';
include 'includes/header.php';

$onglets = [
    'accueil' => 'Vue globale',
    'utilisateurs' => 'Utilisateurs',
    'webtoons' => 'Oeuvres',
    'categories' => 'Categories',
    'traductions' => 'Cache DeepL',
    'base' => 'Tables brutes'
];
?>

<div class="entete-page">
    <div>
        <h1 class="page-titre">Administration</h1>
        <p class="texte-page">Gestion simple de l'application Webtoon-Library.</p>
    </div>
    <a href="admin_db.php?logout_admin=1" class="btn btn-gris">Fermer l'acces admin</a>
</div>

<?php if ($message): ?>
    <div class="alerte alerte-succes"><?= htmlspecialchars($message) ?></div>
<?php endif; ?>

<?php if ($erreur): ?>
    <div class="alerte alerte-erreur"><?= htmlspecialchars($erreur) ?></div>
<?php endif; ?>

<div class="barre-filtres admin-tabs">
    <?php foreach ($onglets as $cle => $label): ?>
        <a href="admin_db.php?onglet=<?= urlencode($cle) ?>"
           class="filtre-btn <?= $onglet === $cle ? 'actif' : '' ?>">
            <?= htmlspecialchars($label) ?>
        </a>
    <?php endforeach; ?>
</div>

<?php if ($onglet === 'accueil'): ?>
    <div class="grille-stats">
        <div class="carte-stat"><div class="stat-nombre"><?= $stats['utilisateurs'] ?></div><div class="stat-label">Utilisateurs</div></div>
        <div class="carte-stat"><div class="stat-nombre"><?= $stats['webtoons'] ?></div><div class="stat-label">Oeuvres en bibliotheque</div></div>
        <div class="carte-stat"><div class="stat-nombre"><?= $stats['categories'] ?></div><div class="stat-label">Categories</div></div>
        <div class="carte-stat"><div class="stat-nombre"><?= $stats['traductions'] ?></div><div class="stat-label">Traductions cachees</div></div>
    </div>
<?php endif; ?>

<?php if ($onglet === 'utilisateurs'): ?>
    <?php $users = $pdo->query("SELECT id, pseudo, email, langue, is_admin, date_creation FROM utilisateurs ORDER BY id DESC")->fetchAll(); ?>
    <div class="admin-db-wrapper">
        <table class="admin-db-table">
            <thead><tr><th>ID</th><th>Pseudo</th><th>Email</th><th>Langue</th><th>Admin</th><th>Nouveau mot de passe</th><th>Actions</th></tr></thead>
            <tbody>
            <?php foreach ($users as $user): ?>
                <tr>
                    <form method="POST" action="admin_db.php?onglet=utilisateurs">
                        <?= champCsrf() ?>
                        <td><?= (int)$user['id'] ?><input type="hidden" name="id" value="<?= (int)$user['id'] ?>"></td>
                        <td><input type="text" name="pseudo" value="<?= htmlspecialchars($user['pseudo']) ?>"></td>
                        <td><input type="email" name="email" value="<?= htmlspecialchars($user['email']) ?>"></td>
                        <td>
                            <select name="langue">
                                <option value="en" <?= $user['langue'] === 'en' ? 'selected' : '' ?>>English</option>
                                <option value="fr" <?= $user['langue'] === 'fr' ? 'selected' : '' ?>>Francais</option>
                            </select>
                        </td>
                        <td><input type="checkbox" name="is_admin" value="1" <?= !empty($user['is_admin']) ? 'checked' : '' ?>></td>
                        <td><input type="password" name="password" placeholder="6 caracteres minimum"></td>
                        <td class="admin-db-actions">
                            <button class="btn btn-vert btn-carte" name="action" value="user_update">Enregistrer</button>
                            <button class="btn btn-gris btn-carte" name="action" value="user_password">Changer MDP</button>
                        </td>
                    </form>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
<?php endif; ?>

<?php if ($onglet === 'webtoons'): ?>
    <?php
    $webtoons = $pdo->query(
        "SELECT w.id, u.pseudo, w.titre, w.genre, w.statut, w.chapitre_actuel, w.note, w.intention, w.date_ajout
         FROM webtoons w
         LEFT JOIN utilisateurs u ON u.id = w.id_utilisateur
         ORDER BY w.date_ajout DESC
         LIMIT 150"
    )->fetchAll();
    ?>
    <div class="admin-db-wrapper">
        <table class="admin-db-table">
            <thead><tr><th>ID</th><th>Utilisateur</th><th>Titre</th><th>Genres</th><th>Statut</th><th>Chapitre</th><th>Note</th><th>Intention</th><th>Date</th></tr></thead>
            <tbody>
            <?php foreach ($webtoons as $wt): ?>
                <tr>
                    <td><?= (int)$wt['id'] ?></td>
                    <td><?= htmlspecialchars($wt['pseudo'] ?? 'Inconnu') ?></td>
                    <td><?= htmlspecialchars($wt['titre']) ?></td>
                    <td><?= htmlspecialchars($wt['genre'] ?? '') ?></td>
                    <td><?= htmlspecialchars($wt['statut'] ?? '') ?></td>
                    <td><?= (int)($wt['chapitre_actuel'] ?? 0) ?></td>
                    <td><?= htmlspecialchars((string)($wt['note'] ?? '')) ?></td>
                    <td><?= htmlspecialchars($wt['intention'] ?? '') ?></td>
                    <td><?= htmlspecialchars($wt['date_ajout'] ?? '') ?></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
<?php endif; ?>

<?php if ($onglet === 'categories'): ?>
    <?php $categories = $pdo->query("SELECT * FROM categories_admin ORDER BY ordre_affichage ASC, nom_anilist ASC")->fetchAll(); ?>
    <div class="admin-db-wrapper">
        <table class="admin-db-table">
            <thead><tr><th>ID</th><th>Nom AniList</th><th>Label FR</th><th>Label EN</th><th>Actif</th><th>+18</th><th>Ordre</th><th>Actions</th></tr></thead>
            <tbody>
            <?php foreach ($categories as $cat): ?>
                <tr>
                    <form method="POST" action="admin_db.php?onglet=categories">
                        <?= champCsrf() ?>
                        <td><?= (int)$cat['id'] ?><input type="hidden" name="id" value="<?= (int)$cat['id'] ?>"></td>
                        <td><input type="text" name="nom_anilist" value="<?= htmlspecialchars($cat['nom_anilist']) ?>"></td>
                        <td><input type="text" name="label_fr" value="<?= htmlspecialchars($cat['label_fr']) ?>"></td>
                        <td><input type="text" name="label_en" value="<?= htmlspecialchars($cat['label_en']) ?>"></td>
                        <td><input type="checkbox" name="actif" value="1" <?= !empty($cat['actif']) ? 'checked' : '' ?>></td>
                        <td><input type="checkbox" name="adulte" value="1" <?= !empty($cat['adulte']) ? 'checked' : '' ?>></td>
                        <td><input type="number" name="ordre_affichage" value="<?= (int)$cat['ordre_affichage'] ?>"></td>
                        <td class="admin-db-actions">
                            <button class="btn btn-vert btn-carte" name="action" value="category_update">Enregistrer</button>
                            <button class="btn-supprimer btn-carte" name="action" value="category_delete" onclick="return confirm('Supprimer cette categorie ?')">Supprimer</button>
                        </td>
                    </form>
                </tr>
            <?php endforeach; ?>
                <tr>
                    <form method="POST" action="admin_db.php?onglet=categories">
                        <?= champCsrf() ?>
                        <td>Nouveau</td>
                        <td><input type="text" name="nom_anilist" placeholder="Ex : Action"></td>
                        <td><input type="text" name="label_fr" placeholder="Ex : Action"></td>
                        <td><input type="text" name="label_en" placeholder="Ex : Action"></td>
                        <td colspan="2">Actif par defaut</td>
                        <td><input type="number" name="ordre_affichage" value="200"></td>
                        <td><button class="btn btn-vert btn-carte" name="action" value="category_add">Ajouter</button></td>
                    </form>
                </tr>
            </tbody>
        </table>
    </div>
<?php endif; ?>

<?php if ($onglet === 'traductions'): ?>
    <?php $traductions = $pdo->query("SELECT id, langue_cible, texte_original, texte_traduit, date_creation FROM traductions_cache ORDER BY id DESC LIMIT 100")->fetchAll(); ?>
    <div class="admin-db-wrapper">
        <table class="admin-db-table">
            <thead><tr><th>ID</th><th>Langue</th><th>Original</th><th>Traduit</th><th>Date</th></tr></thead>
            <tbody>
            <?php foreach ($traductions as $traduction): ?>
                <tr>
                    <td><?= (int)$traduction['id'] ?></td>
                    <td><?= htmlspecialchars($traduction['langue_cible']) ?></td>
                    <td><?= htmlspecialchars(substr($traduction['texte_original'], 0, 160)) ?></td>
                    <td><?= htmlspecialchars(substr($traduction['texte_traduit'], 0, 160)) ?></td>
                    <td><?= htmlspecialchars($traduction['date_creation']) ?></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
<?php endif; ?>

<?php if ($onglet === 'base'): ?>
    <?php
    $tables = tablesSqlite($pdo);
    $tableActive = $_GET['table'] ?? ($tables[0] ?? '');
    if (!in_array($tableActive, $tables, true)) {
        $tableActive = $tables[0] ?? '';
    }
    $colonnes = $tableActive ? colonnesSqlite($pdo, $tableActive) : [];
    $pk = colonnePrimaire($colonnes);
    $lignes = $tableActive ? $pdo->query("SELECT * FROM " . nomSql($tableActive) . " ORDER BY " . nomSql($pk) . " DESC LIMIT 100")->fetchAll() : [];
    ?>
    <div class="barre-filtres">
        <?php foreach ($tables as $table): ?>
            <a href="admin_db.php?onglet=base&table=<?= urlencode($table) ?>" class="filtre-btn <?= $tableActive === $table ? 'actif' : '' ?>"><?= htmlspecialchars($table) ?></a>
        <?php endforeach; ?>
    </div>
    <div class="admin-db-wrapper">
        <table class="admin-db-table">
            <thead>
                <tr>
                    <?php foreach ($colonnes as $colonne): ?><th><?= htmlspecialchars($colonne['name']) ?></th><?php endforeach; ?>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($lignes as $ligne): ?>
                    <tr>
                        <form method="POST" action="admin_db.php?onglet=base&table=<?= urlencode($tableActive) ?>">
                            <?= champCsrf() ?>
                            <input type="hidden" name="table" value="<?= htmlspecialchars($tableActive) ?>">
                            <?php foreach ($colonnes as $colonne): ?>
                                <?php $nom = $colonne['name']; ?>
                                <td>
                                    <?php if ($nom === $pk): ?>
                                        <strong><?= htmlspecialchars((string)$ligne[$nom]) ?></strong>
                                        <input type="hidden" name="id" value="<?= htmlspecialchars((string)$ligne[$nom]) ?>">
                                    <?php else: ?>
                                        <input type="text" name="<?= htmlspecialchars($nom) ?>" value="<?= htmlspecialchars((string)($ligne[$nom] ?? '')) ?>">
                                    <?php endif; ?>
                                </td>
                            <?php endforeach; ?>
                            <td><button class="btn btn-vert btn-carte" name="action" value="raw_update">Enregistrer</button></td>
                        </form>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
<?php endif; ?>

<?php include 'includes/footer.php'; ?>

