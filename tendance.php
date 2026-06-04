<?php
// =============================================
// tendance.php - Tendances AniList par sections
// =============================================

session_start();
include 'includes/config.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: connexion.php');
    exit;
}

$userId = $_SESSION['user_id'];
$inclureAdulte = !empty($_SESSION['inclure_adulte']);
$erreur = '';
$succes = '';
$erreursAnilist = [];

$categories = [
    'general' => 'General',
    'Action' => 'Action',
    'Fantasy' => 'Fantasy',
    'Romance' => 'Romance',
    'Drama' => 'Drama',
    'Comedy' => 'Comedy',
    'Horror' => 'Horror',
    'Adventure' => 'Adventure',
    'Mystery' => 'Mystery',
    'Sports' => 'Sports'
];

$categoriesAdultes = $categories;
unset($categoriesAdultes['Sports']);

$sections = [
    'tendance' => [
        'titre' => 'Tendance',
        'description' => 'Les oeuvres les plus suivies en ce moment sur AniList.',
        'bouton' => 'Voir plus de tendances',
        'categories' => $categories,
        'sort' => 'TRENDING_DESC',
        'status' => null,
        'startDateGreater' => null,
        'isAdult' => false
    ],
    'future' => [
        'titre' => 'Future pepite',
        'description' => 'Des oeuvres recentes et en cours qui peuvent encore monter.',
        'bouton' => 'Voir plus de futures pepites',
        'categories' => $categories,
        'sort' => 'TRENDING_DESC',
        'status' => 'RELEASING',
        'startDateGreater' => ((int)date('Y') - 2) . '0101',
        'isAdult' => false
    ],
    'ancienne' => [
        'titre' => 'Ancienne pepite',
        'description' => 'Des oeuvres terminees qui restent tres populaires.',
        'bouton' => "Voir plus d'anciennes pepites",
        'categories' => $categories,
        'sort' => 'POPULARITY_DESC',
        'status' => 'FINISHED',
        'startDateGreater' => null,
        'isAdult' => false
    ]
];

if ($inclureAdulte) {
    $sections['adulte'] = [
        'titre' => 'Top +18',
        'description' => 'Selection adulte affichee uniquement si le +18 est active dans les parametres.',
        'bouton' => 'Voir plus de +18',
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
        curl_close($curl);
        return [];
    }

    curl_close($curl);

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

function nettoyerDescriptionTendance($description)
{
    $description = html_entity_decode($description ?? '', ENT_QUOTES, 'UTF-8');
    $description = strip_tags($description);
    $description = trim(preg_replace('/\s+/', ' ', $description));

    if (strlen($description) > 160) {
        $description = substr($description, 0, 160) . '...';
    }

    return $description;
}

function titreAnilistTendance($webtoon)
{
    return ($webtoon['title']['english'] ?? '')
        ?: (($webtoon['title']['romaji'] ?? '')
        ?: (($webtoon['title']['native'] ?? '') ?: 'Titre inconnu'));
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
    $description = nettoyerDescriptionTendance($wt['description'] ?? '');
    $genres = implode(', ', $wt['genres'] ?? []);
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
                <button type="button" class="btn btn-gris btn-carte btn-desactive" disabled>Deja ajoute</button>
            <?php else: ?>
                <form method="POST" action="<?= htmlspecialchars($urlRetour) ?>">
                    <input type="hidden" name="retour" value="<?= htmlspecialchars($urlRetour) ?>">
                    <input type="hidden" name="anilist_id" value="<?= (int)$wt['id'] ?>">
                    <input type="hidden" name="titre" value="<?= htmlspecialchars($titreCarte) ?>">
                    <input type="hidden" name="genres" value="<?= htmlspecialchars($genres) ?>">
                    <input type="hidden" name="description" value="<?= htmlspecialchars($description) ?>">
                    <input type="hidden" name="image_url" value="<?= htmlspecialchars($image) ?>">
                    <button type="submit" class="btn btn-vert btn-carte">Ajouter</button>
                </form>
            <?php endif; ?>
            <a href="detail_webtoon.php?id=<?= (int)$wt['id'] ?>" class="btn btn-gris btn-carte">Voir details</a>
        </div>
    </div>
    <?php
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $anilistId = (int)($_POST['anilist_id'] ?? 0);
    $titreAnilist = trim($_POST['titre'] ?? '');
    $genresAnilist = trim($_POST['genres'] ?? '');
    $descriptionAnilist = trim($_POST['description'] ?? '');
    $imageAnilist = trim($_POST['image_url'] ?? '');
    $retour = $_POST['retour'] ?? 'tendance.php';

    if (strpos($retour, 'tendance.php') !== 0) {
        $retour = 'tendance.php';
    }

    if ($anilistId > 0 && $titreAnilist !== '') {
        $reqExiste = $pdo->prepare(
            "SELECT id FROM webtoons WHERE id_utilisateur = ? AND titre = ? LIMIT 1"
        );
        $reqExiste->execute([$userId, $titreAnilist]);

        if ($reqExiste->fetch()) {
            $erreur = "Ce webtoon est deja dans votre bibliotheque.";
        } else {
            $insert = $pdo->prepare(
                "INSERT INTO webtoons
                 (id_utilisateur, titre, auteur, genre, description, statut, chapitre_actuel, note, image_url)
                 VALUES (?, ?, ?, ?, ?, 'a_lire', 0, NULL, ?)"
            );
            $insert->execute([
                $userId,
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
        $erreur = "Impossible d'ajouter ce webtoon.";
    }
}

if (isset($_GET['ajout'])) {
    $succes = "Webtoon ajoute a votre bibliotheque.";
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

$titre_page = "Tendance";
include 'includes/header.php';
?>

<div class="entete-page">
    <div>
        <h1 class="page-titre">Tendance</h1>
        <p class="texte-page">Explore les tendances, les futures pepites et les classiques populaires depuis AniList.</p>
    </div>
    <a href="webtoons.php" class="btn btn-gris">Voir ma liste</a>
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
            <span class="badge-section-tendance"><?= (int)($pageActive * 15) ?> resultats</span>
        </div>

        <div class="barre-categories-tendance">
            <?php foreach ($section['categories'] as $cleCategorie => $nomCategorie): ?>
                <a href="<?= htmlspecialchars(urlTendance([
                    $paramCategorie => $cleCategorie,
                    $paramPage => 1
                ])) ?>#<?= htmlspecialchars($cleSection) ?>"
                   class="btn-categorie-tendance <?= $categorieActive === $cleCategorie ? 'actif' : '' ?>">
                    <?= htmlspecialchars($nomCategorie) ?>
                </a>
            <?php endforeach; ?>
        </div>

        <?php if (empty($etatSection['webtoons'])): ?>
            <div class="message-vide message-vide-compact">
                <img src="<?= $base ?>/assets/img/icon-empty.svg" alt="" class="icone-vide-svg">
                <p>Aucun resultat pour cette section.</p>
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
