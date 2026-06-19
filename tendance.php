<?php
// =============================================
// tendance.php - Tendances AniList par sections
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
$inclureAdulte = !empty($_SESSION['inclure_adulte']);
$erreur = '';
$succes = '';
$erreursAnilist = [];

$categories = ['general' => 'General'] + chargerCategoriesAdmin($pdo, true);

$categoriesAdultes = ['general' => 'General'] + chargerCategoriesAdmin($pdo, false);

$sections = [
    'tendance' => [
        'titre' => t('trends'),
        'description' => langueCourante() === 'fr'
            ? 'Les oeuvres les plus suivies en ce moment sur AniList.'
            : 'The most followed works right now on AniList.',
        'bouton' => t('more_trends'),
        'categories' => $categories,
        'sort' => 'TRENDING_DESC',
        'status' => null,
        'startDateGreater' => null,
        'isAdult' => false
    ],
    'future' => [
        'titre' => t('future_gem'),
        'description' => langueCourante() === 'fr'
            ? 'Des oeuvres recentes et en cours qui peuvent encore monter.'
            : 'Recent ongoing works that could still rise.',
        'bouton' => t('more_future'),
        'categories' => $categories,
        'sort' => 'TRENDING_DESC',
        'status' => 'RELEASING',
        'startDateGreater' => ((int)date('Y') - 2) . '0101',
        'isAdult' => false
    ],
    'ancienne' => [
        'titre' => t('old_gem'),
        'description' => langueCourante() === 'fr'
            ? 'Des oeuvres terminees qui restent tres populaires.'
            : 'Finished works that are still very popular.',
        'bouton' => t('more_old'),
        'categories' => $categories,
        'sort' => 'POPULARITY_DESC',
        'status' => 'FINISHED',
        'startDateGreater' => null,
        'isAdult' => false
    ]
];

if ($inclureAdulte) {
    $sections['adulte'] = [
        'titre' => t('top_adult'),
        'description' => langueCourante() === 'fr'
            ? 'Selection adulte affichee uniquement si le +18 est active dans les parametres.'
            : 'Adult selection shown only when +18 is enabled in settings.',
        'bouton' => t('more_adult'),
        'categories' => $categoriesAdultes,
        'sort' => 'POPULARITY_DESC',
        'status' => null,
        'startDateGreater' => null,
        'isAdult' => true
    ];
}

function appelerAnilistTendance($query, $variables, &$erreurApi)
{
    if (!function_exists('curl_init')) {
        $erreurApi = langueCourante() === 'fr'
            ? "L'extension PHP cURL n'est pas activee. AniList ne peut pas etre interroge."
            : 'The PHP cURL extension is not enabled. AniList cannot be reached.';
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
        $erreurApi = (langueCourante() === 'fr'
            ? 'AniList ne repond pas pour le moment : '
            : 'AniList is not responding right now: ') . curl_error($curl);
        return [];
    }


    if ($httpCode !== 200) {
        $erreurApi = (langueCourante() === 'fr'
            ? 'AniList a retourne une erreur HTTP '
            : 'AniList returned HTTP error ') . $httpCode . '.';
        return [];
    }

    $data = json_decode($response, true);

    if (!isset($data['data']['Page']['media'])) {
        $erreurApi = langueCourante() === 'fr'
            ? 'La reponse AniList est invalide.'
            : 'The AniList response is invalid.';
        return [];
    }

    return $data['data']['Page']['media'];
}

function recupererSectionAnilist($section, $genre, $page, &$erreurApi)
{
    $perPage = max(1, $page) * 15;
    $definitionStatus = $section['status'] ? ', $status: MediaStatus' : '';
    $argumentStatus = $section['status'] ? ', status: $status' : '';
    $definitionDate = $section['startDateGreater'] ? ', $startDateGreater: FuzzyDateInt' : '';
    $argumentDate = $section['startDateGreater'] ? ', startDate_greater: $startDateGreater' : '';

    $query = '
        query (
            $perPage: Int!,
            $genre: String,
            $isAdult: Boolean,
            $sort: [MediaSort]' . $definitionStatus . $definitionDate . '
        ) {
            Page(page: 1, perPage: $perPage) {
                media(
                    type: MANGA,
                    countryOfOrigin: "KR",
                    genre: $genre,
                    isAdult: $isAdult,
                    sort: $sort' . $argumentStatus . $argumentDate . '
                ) {
                    id
                    title { romaji english native }
                    description(asHtml: false)
                    genres
                    coverImage { large }
                }
            }
        }
    ';

    $variables = [
        'perPage' => $perPage,
        'genre' => $genre !== '' ? $genre : null,
        'isAdult' => $section['isAdult'],
        'sort' => [$section['sort']]
    ];

    if ($section['status']) {
        $variables['status'] = $section['status'];
    }

    if ($section['startDateGreater']) {
        $variables['startDateGreater'] = (int)$section['startDateGreater'];
    }

    return appelerAnilistTendance($query, $variables, $erreurApi);
}

function titreAnilistTendance($webtoon)
{
    return ($webtoon['title']['english'] ?? '')
        ?: (($webtoon['title']['romaji'] ?? '')
        ?: (($webtoon['title']['native'] ?? '') ?: 'Unknown title'));
}

function urlTendance($parametres)
{
    $query = $_GET;
    unset($query['ajout']);

    foreach ($parametres as $cle => $valeur) {
        $query[$cle] = $valeur;
    }

    return 'tendance.php' . (!empty($query) ? '?' . http_build_query($query) : '');
}

function urlTendanceCourante()
{
    $query = $_GET;
    unset($query['ajout']);

    return 'tendance.php' . (!empty($query) ? '?' . http_build_query($query) : '');
}

function ajouterParametreUrl($url, $cle, $valeur)
{
    $separateur = strpos($url, '?') === false ? '?' : '&';
    return $url . $separateur . urlencode($cle) . '=' . urlencode($valeur);
}

function afficherCarteTendance($wt, $mesTitres, $base, $urlRetour)
{
    $titreCarte = titreAnilistTendance($wt);
    $description = descriptionSelonLangue($wt['description'] ?? '', 160);
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
                <form method="POST" action="<?= htmlspecialchars($urlRetour) ?>">
                    <?= champCsrf() ?>
                    <input type="hidden" name="retour" value="<?= htmlspecialchars($urlRetour) ?>">
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
    <?php
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifierCsrf()) {
        refuserRequeteInvalide();
    }

    $anilistId = (int)($_POST['anilist_id'] ?? 0);
    $titreAnilist = trim($_POST['titre'] ?? '');
    $genresAnilist = traduireGenres(trim($_POST['genres'] ?? ''));
    $descriptionAnilist = descriptionSelonLangue($_POST['description'] ?? '');
    $imageAnilist = trim($_POST['image_url'] ?? '');
    $retour = $_POST['retour'] ?? 'tendance.php';

    if (strpos($retour, 'tendance.php') !== 0) {
        $retour = 'tendance.php';
    }

    if ($anilistId > 0 && $titreAnilist !== '') {
        $reqExiste = $pdo->prepare(
            "SELECT id FROM webtoons
             WHERE id_utilisateur = ? AND (anilist_id = ? OR titre = ?)
             LIMIT 1"
        );
        $reqExiste->execute([$userId, $anilistId, $titreAnilist]);

        if ($reqExiste->fetch()) {
            $erreur = t('already_in_library');
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

            header('Location: ' . ajouterParametreUrl($retour, 'ajout', 'ok'));
            exit;
        }
    } else {
        $erreur = t('add_error');
    }
}

if (isset($_GET['ajout'])) {
    $succes = t('added_to_library');
}

$reqMesWebtoons = $pdo->prepare("SELECT titre FROM webtoons WHERE id_utilisateur = ?");
$reqMesWebtoons->execute([$userId]);
$mesTitres = [];

foreach ($reqMesWebtoons->fetchAll() as $wt) {
    $mesTitres[strtolower($wt['titre'])] = true;
}

$resultatsSections = [];

foreach ($sections as $cleSection => $section) {
    $paramCategorie = 'cat_' . $cleSection;
    $paramPage = 'page_' . $cleSection;

    $categorieActive = $_GET[$paramCategorie] ?? 'general';
    if (!array_key_exists($categorieActive, $section['categories'])) {
        $categorieActive = 'general';
    }

    $pageActive = max(1, (int)($_GET[$paramPage] ?? 1));
    $genreActif = $categorieActive === 'general' ? '' : $section['categories'][$categorieActive];
    $erreurSection = '';
    $webtoons = recupererSectionAnilist($section, $genreActif, $pageActive, $erreurSection);

    if ($erreurSection !== '') {
        $erreursAnilist[] = $section['titre'] . ' : ' . $erreurSection;
    }

    $resultatsSections[$cleSection] = [
        'categorieActive' => $categorieActive,
        'pageActive' => $pageActive,
        'webtoons' => $webtoons
    ];
}

$urlRetour = urlTendanceCourante();

$titre_page = t('trends');
include 'includes/header.php';
?>

<div class="entete-page">
    <div>
        <h1 class="page-titre"><?= htmlspecialchars(t('trends')) ?></h1>
        <p class="texte-page"><?= htmlspecialchars(t('trending_intro')) ?></p>
    </div>
    <a href="webtoons.php" class="btn btn-gris"><?= htmlspecialchars(t('my_list')) ?></a>
</div>

<?php if ($erreur): ?>
    <div class="alerte alerte-erreur"><?= htmlspecialchars($erreur) ?></div>
<?php endif; ?>

<?php if ($succes): ?>
    <div class="alerte alerte-succes"><?= htmlspecialchars($succes) ?></div>
<?php endif; ?>

<?php foreach ($erreursAnilist as $messageErreur): ?>
    <div class="alerte alerte-erreur"><?= htmlspecialchars($messageErreur) ?></div>
<?php endforeach; ?>

<?php foreach ($sections as $cleSection => $section): ?>
    <?php
        $etatSection = $resultatsSections[$cleSection];
        $categorieActive = $etatSection['categorieActive'];
        $pageActive = $etatSection['pageActive'];
        $paramCategorie = 'cat_' . $cleSection;
        $paramPage = 'page_' . $cleSection;
    ?>

    <section class="section-tendance bloc-section-tendance" id="<?= htmlspecialchars($cleSection) ?>">
        <div class="entete-section-tendance">
            <div>
                <h2><?= htmlspecialchars($section['titre']) ?></h2>
                <p><?= htmlspecialchars($section['description']) ?></p>
            </div>
            <span class="badge-section-tendance"><?= (int)($pageActive * 15) ?> <?= htmlspecialchars(t('results')) ?></span>
        </div>

        <div class="barre-categories-tendance">
            <?php foreach ($section['categories'] as $cleCategorie => $nomCategorie): ?>
                <a href="<?= htmlspecialchars(urlTendance([
                    $paramCategorie => $cleCategorie,
                    $paramPage => 1
                ])) ?>#<?= htmlspecialchars($cleSection) ?>"
                   class="btn-categorie-tendance <?= $categorieActive === $cleCategorie ? 'actif' : '' ?>">
                    <?= htmlspecialchars($cleCategorie === 'general' ? t('general') : labelCategorieAdmin($pdo, $nomCategorie)) ?>
                </a>
            <?php endforeach; ?>
        </div>

        <?php if (empty($etatSection['webtoons'])): ?>
            <div class="message-vide message-vide-compact">
                <img src="<?= $base ?>/assets/img/icon-empty.svg" alt="" class="icone-vide-svg">
                <p><?= htmlspecialchars(t('no_section_result')) ?></p>
            </div>
        <?php else: ?>
            <div class="grille-webtoons grille-tendance">
                <?php foreach ($etatSection['webtoons'] as $wt): ?>
                    <?php afficherCarteTendance($wt, $mesTitres, $base, $urlRetour); ?>
                <?php endforeach; ?>
            </div>

            <div class="zone-voir-plus">
                <a href="<?= htmlspecialchars(urlTendance([
                    $paramPage => $pageActive + 1
                ])) ?>#<?= htmlspecialchars($cleSection) ?>"
                   class="btn btn-vert btn-voir-plus">
                    <?= htmlspecialchars($section['bouton']) ?>
                </a>
            </div>
        <?php endif; ?>
    </section>
<?php endforeach; ?>

<?php include 'includes/footer.php'; ?>
