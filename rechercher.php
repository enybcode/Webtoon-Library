<?php
// =============================================
// rechercher.php - Recherche AniList et tendances
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
$recherche = trim($_GET['q'] ?? '');
$categoriesRecherche = chargerCategoriesAdmin($pdo, true);
$genresValides = array_keys($categoriesRecherche);
$trisValides = ['popularite', 'note', 'recent'];
$genreFiltre = in_array($_GET['genre'] ?? '', $genresValides) ? $_GET['genre'] : '';
$inclureAdulte = !empty($_SESSION['inclure_adulte']);
$tri = in_array($_GET['sort'] ?? '', $trisValides) ? $_GET['sort'] : 'popularite';
$erreur = '';
$succes = '';
$erreurAnilist = '';
$resultatsAnilist = [];

function appelerAnilist($query, $variables, &$erreurApi)
{
    if (!function_exists('curl_init')) {
        $erreurApi = "L'extension PHP cURL n'est pas activee. AniList ne peut pas etre interroge.";
        return [];
    }

    $curl = curl_init('https://graphql.anilist.co');
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($curl, CURLOPT_POST, true);
    curl_setopt($curl, CURLOPT_POSTFIELDS, json_encode([
        'query' => $query,
        'variables' => $variables
    ]));
    curl_setopt($curl, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
    curl_setopt($curl, CURLOPT_TIMEOUT, 10);

    $response = curl_exec($curl);
    $httpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);

    if ($response === false) {
        $erreurApi = "AniList ne repond pas pour le moment : " . curl_error($curl);
        return [];
    }


    if ($httpCode !== 200) {
        $erreurApi = "AniList a retourne une erreur HTTP " . $httpCode . ".";
        return [];
    }

    $data = json_decode($response, true);

    if (!isset($data['data']['Page']['media'])) {
        $erreurApi = "La reponse AniList est invalide.";
        return [];
    }

    return $data['data']['Page']['media'];
}

function rechercherAnilist($search, $genre, $inclureAdulte, $tri, &$erreurApi)
{
    $query = '
        query ($search: String!, $genre: String, $isAdult: Boolean, $sort: [MediaSort]) {
            Page(page: 1, perPage: 12) {
                media(search: $search, type: MANGA, genre: $genre, isAdult: $isAdult, sort: $sort) {
                    id
                    title { romaji english native }
                    description(asHtml: false)
                    genres
                    coverImage { large }
                }
            }
        }
    ';

    $trisAnilist = [
        'popularite' => 'POPULARITY_DESC',
        'note' => 'SCORE_DESC',
        'recent' => 'START_DATE_DESC'
    ];

    return appelerAnilist($query, [
        'search' => $search,
        'genre' => $genre !== '' ? $genre : null,
        'isAdult' => $inclureAdulte,
        'sort' => [$trisAnilist[$tri] ?? 'POPULARITY_DESC']
    ], $erreurApi);
}

function titreAnilist($webtoon)
{
    return $webtoon['title']['english']
        ?: ($webtoon['title']['romaji']
        ?: ($webtoon['title']['native'] ?: 'Titre inconnu'));
}

// ===== AJOUT D'UN RESULTAT ANILIST A LA BIBLIOTHEQUE =====
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifierCsrf()) {
        refuserRequeteInvalide();
    }

    $anilistId = (int)($_POST['anilist_id'] ?? 0);
    $titreAnilist = trim($_POST['titre'] ?? '');
    $genresAnilist = traduireGenres(trim($_POST['genres'] ?? ''));
    $descriptionAnilist = descriptionFrancaise($_POST['description'] ?? '');
    $imageAnilist = trim($_POST['image_url'] ?? '');

    if ($anilistId > 0 && $titreAnilist !== '') {
        $reqExiste = $pdo->prepare(
            "SELECT id FROM webtoons
             WHERE id_utilisateur = ? AND (anilist_id = ? OR titre = ?)
             LIMIT 1"
        );
        $reqExiste->execute([$userId, $anilistId, $titreAnilist]);

        if ($reqExiste->fetch()) {
            $erreur = "Ce webtoon est deja dans votre bibliotheque.";
        } else {
            $insert = $pdo->prepare(
                "INSERT INTO webtoons
                 (id_utilisateur, anilist_id, titre, auteur, genre, description, statut, chapitre_actuel, note, intention, image_url)
                 VALUES (?, ?, ?, ?, ?, ?, 'a_lire', 0, NULL, 'hesite', ?)"
            );
            $insert->execute([
                $userId,
                $anilistId,
                $titreAnilist,
                'AniList',
                $genresAnilist,
                $descriptionAnilist,
                $imageAnilist
            ]);

            header('Location: rechercher.php?ajout=ok');
            exit;
        }
    } else {
        $erreur = t('add_error');
    }
}

if (isset($_GET['ajout'])) {
    $succes = "Webtoon ajoute a votre bibliotheque.";
}

if ($recherche !== '') {
    $resultatsAnilist = rechercherAnilist($recherche, $genreFiltre, $inclureAdulte, $tri, $erreurAnilist);
}

// Titres deja presents dans la bibliotheque de l'utilisateur.
$reqMesWebtoons = $pdo->prepare("SELECT titre FROM webtoons WHERE id_utilisateur = ?");
$reqMesWebtoons->execute([$userId]);
$mesTitres = [];

foreach ($reqMesWebtoons->fetchAll() as $wt) {
    $mesTitres[strtolower($wt['titre'])] = true;
}

$titre_page = t('search');
include 'includes/header.php';
?>

<div class="bloc-recherche">
    <div class="entete-recherche">
        <h1 class="page-titre"><?= htmlspecialchars(t('search')) ?></h1>
        <button type="button" class="btn-filtres" onclick="toggleFiltres()"><?= htmlspecialchars(t('filters')) ?></button>
    </div>

    <form class="search-form-anilist" method="GET" action="rechercher.php">
        <div class="search-bar">
            <span class="search-icon">
                <img src="<?= $base ?>/assets/img/icon-search.svg" alt="">
            </span>
            <input class="search-input-modern"
                   type="text"
                   name="q"
                   placeholder="<?= htmlspecialchars(t('search_placeholder')) ?>"
                   value="<?= htmlspecialchars($recherche) ?>">
            <button class="search-button-modern" type="submit"><?= htmlspecialchars(t('search')) ?></button>
            <?php if ($recherche !== ''): ?>
                <a href="rechercher.php" class="search-reset"><?= langueCourante() === 'fr' ? 'RÃ©initialiser' : 'Reset' ?></a>
            <?php endif; ?>
        </div>

        <div id="zone-filtres" class="zone-filtres <?= ($genreFiltre !== '' || $tri !== 'popularite') ? 'ouverte' : '' ?>">
            <div class="groupe-filtre">
                <label for="genre"><?= htmlspecialchars(t('genre')) ?></label>
                <select id="genre" name="genre">
                    <option value=""><?= htmlspecialchars(t('all_genres')) ?></option>
                    <?php foreach ($genresValides as $genre): ?>
                        <option value="<?= htmlspecialchars($genre) ?>" <?= $genreFiltre === $genre ? 'selected' : '' ?>>
                            <?= htmlspecialchars(labelCategorieAdmin($pdo, $genre)) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="groupe-filtre">
                <label for="sort"><?= htmlspecialchars(t('sort_by')) ?></label>
                <select id="sort" name="sort">
                    <option value="popularite" <?= $tri === 'popularite' ? 'selected' : '' ?>><?= htmlspecialchars(t('popularity')) ?></option>
                    <option value="note" <?= $tri === 'note' ? 'selected' : '' ?>><?= htmlspecialchars(t('score')) ?></option>
                    <option value="recent" <?= $tri === 'recent' ? 'selected' : '' ?>><?= htmlspecialchars(t('recent')) ?></option>
                </select>
            </div>

            <button class="btn-appliquer-filtres" type="submit"><?= htmlspecialchars(t('apply')) ?></button>
        </div>
    </form>
</div>

<?php if ($erreur): ?>
    <div class="alerte alerte-erreur"><?= htmlspecialchars($erreur) ?></div>
<?php endif; ?>

<?php if ($succes): ?>
    <div class="alerte alerte-succes"><?= htmlspecialchars($succes) ?></div>
<?php endif; ?>

<?php if ($erreurAnilist): ?>
    <div class="alerte alerte-erreur"><?= htmlspecialchars($erreurAnilist) ?></div>
<?php endif; ?>

<?php if (empty($resultatsAnilist)): ?>
    <div class="message-vide">
        <img src="<?= $base ?>/assets/img/icon-empty.svg" alt="" class="icone-vide-svg">
        <p><?= $recherche !== '' ? htmlspecialchars(t('no_result')) : htmlspecialchars(t('search_start')) ?></p>
    </div>
<?php else: ?>
    <div class="grille-webtoons">
        <?php foreach ($resultatsAnilist as $wt): ?>
            <?php
                $titreCarte = titreAnilist($wt);
                $description = descriptionFrancaise($wt['description'] ?? '', 180);
                $genres = traduireGenres($wt['genres'] ?? []);
                $image = $wt['coverImage']['large'] ?? '';
                $dejaAjoute = isset($mesTitres[strtolower($titreCarte)]);
            ?>

            <div class="carte-webtoon">
                <?php if (!empty($image)): ?>
                    <img class="carte-webtoon-image"
                         src="<?= htmlspecialchars($image) ?>"
                         alt="<?= htmlspecialchars($titreCarte) ?>"
                         onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';">
                    <div class="carte-webtoon-image-placeholder" style="display:none;">
                        <img src="<?= $base ?>/assets/img/icon-book.svg" alt="" class="placeholder-icon">
                    </div>
                <?php else: ?>
                    <div class="carte-webtoon-image-placeholder">
                        <img src="<?= $base ?>/assets/img/icon-book.svg" alt="" class="placeholder-icon">
                    </div>
                <?php endif; ?>

                <div class="carte-webtoon-corps">
                    <div class="carte-webtoon-titre"><?= htmlspecialchars($titreCarte) ?></div>
                    <?php if (!empty($genres)): ?>
                        <div class="carte-webtoon-auteur">
                            <em><?= htmlspecialchars($genres) ?></em>
                        </div>
                    <?php endif; ?>

                </div>

                <div class="carte-webtoon-actions">
                    <?php if ($dejaAjoute): ?>
                        <button type="button" class="btn btn-gris btn-carte btn-desactive" disabled><?= htmlspecialchars(t('already_added')) ?></button>
                    <?php else: ?>
                        <form method="POST" action="rechercher.php">
                            <?= champCsrf() ?>
                            <input type="hidden" name="anilist_id" value="<?= (int)$wt['id'] ?>">
                            <input type="hidden" name="titre" value="<?= htmlspecialchars($titreCarte) ?>">
                            <input type="hidden" name="genres" value="<?= htmlspecialchars($genres) ?>">
                            <input type="hidden" name="description" value="<?= htmlspecialchars($description) ?>">
                            <input type="hidden" name="image_url" value="<?= htmlspecialchars($image) ?>">
                            <button type="submit" class="btn btn-vert btn-carte"><?= htmlspecialchars(t('add')) ?></button>
                        </form>
                    <?php endif; ?>
                    <a href="detail_webtoon.php?id=<?= (int)$wt['id'] ?>" class="btn btn-gris btn-carte"><?= htmlspecialchars(t('details')) ?></a>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
<?php endif; ?>

<script>
function toggleFiltres() {
    const zone = document.getElementById('zone-filtres');
    if (zone) {
        zone.classList.toggle('ouverte');
    }
}

</script>

<?php include 'includes/footer.php'; ?>
