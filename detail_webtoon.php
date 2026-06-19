<?php
// =============================================
// detail_webtoon.php - Detail complet AniList
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
$idAnilist = (int)($_GET['id'] ?? 0);
$erreur = '';
$succes = '';
$erreurAnilist = '';
$webtoon = null;

if ($idAnilist <= 0) {
    header('Location: rechercher.php');
    exit;
}

function appelerAnilistDetail($idAnilist, &$erreurApi)
{
    if (!function_exists('curl_init')) {
        $erreurApi = langueCourante() === 'fr'
            ? "L'extension PHP cURL n'est pas activee. AniList ne peut pas etre interroge."
            : 'The PHP cURL extension is not enabled. AniList cannot be reached.';
        return null;
    }

    $query = '
        query ($id: Int!) {
            Media(id: $id, type: MANGA) {
                id
                title { romaji english native }
                description(asHtml: false)
                coverImage { large }
                bannerImage
                genres
                chapters
                volumes
                status
                averageScore
                meanScore
                popularity
                favourites
                countryOfOrigin
                startDate { year month day }
                endDate { year month day }
                siteUrl
                characters(page: 1, perPage: 6) {
                    nodes {
                        name { full }
                        image { medium }
                    }
                }
                staff(page: 1, perPage: 6) {
                    nodes {
                        name { full }
                        image { medium }
                    }
                }
                relations {
                    edges {
                        node {
                            id
                            title { romaji english native }
                            coverImage { medium }
                        }
                    }
                }
            }
        }
    ';

    $curl = curl_init('https://graphql.anilist.co');
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($curl, CURLOPT_POST, true);
    curl_setopt($curl, CURLOPT_POSTFIELDS, json_encode([
        'query' => $query,
        'variables' => ['id' => $idAnilist]
    ]));
    curl_setopt($curl, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
    curl_setopt($curl, CURLOPT_TIMEOUT, 10);

    $response = curl_exec($curl);
    $httpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);

    if ($response === false) {
        $erreurApi = (langueCourante() === 'fr' ? 'AniList ne repond pas pour le moment : ' : 'AniList is not responding right now: ') . curl_error($curl);
        return null;
    }


    if ($httpCode !== 200) {
        $erreurApi = (langueCourante() === 'fr' ? 'AniList a retourne une erreur HTTP ' : 'AniList returned HTTP error ') . $httpCode . '.';
        return null;
    }

    $data = json_decode($response, true);

    if (!isset($data['data']['Media'])) {
        $erreurApi = langueCourante() === 'fr' ? 'La reponse AniList est invalide.' : 'The AniList response is invalid.';
        return null;
    }

    return $data['data']['Media'];
}

function titreDetailAnilist($webtoon)
{
    return ($webtoon['title']['english'] ?? '')
        ?: (($webtoon['title']['romaji'] ?? '')
        ?: (($webtoon['title']['native'] ?? '') ?: 'Unknown title'));
}

function nettoyerDescriptionDetail($description)
{
    return descriptionFrancaise($description);
}

function statutDetailFrancais($statut)
{
    return traduireStatut($statut);
}

function paysDetailFrancais($pays)
{
    return traduirePays($pays);
}

function dateDetailFrancais($date)
{
    return formaterDateFr($date);
}

function valeurDetail($valeur, $suffixe = '')
{
    if ($valeur === null || $valeur === '') {
        return t('not_filled');
    }

    return htmlspecialchars((string)$valeur . $suffixe);
}

function petiteCarteDetail($titre, $image, $lien = '')
{
    ?>
    <?php if ($lien !== ''): ?>
        <a href="<?= htmlspecialchars($lien) ?>" class="detail-mini-card">
    <?php else: ?>
        <div class="detail-mini-card">
    <?php endif; ?>
            <?php if (!empty($image)): ?>
                <img src="<?= htmlspecialchars($image) ?>" alt="<?= htmlspecialchars($titre) ?>">
            <?php else: ?>
                <div class="detail-mini-placeholder">WL</div>
            <?php endif; ?>
            <span><?= htmlspecialchars($titre) ?></span>
    <?php if ($lien !== ''): ?>
        </a>
    <?php else: ?>
        </div>
    <?php endif; ?>
    <?php
}

function statutSuiviFrancais($statut)
{
    return traduireStatut($statut);
}

function intentionSuiviFrancais($intention)
{
    $intentions = [
        'continuer' => t('continue'),
        'hesite' => t('unsure'),
        'arreter' => t('stop')
    ];

    return $intentions[$intention] ?? t('unsure');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifierCsrf()) {
        refuserRequeteInvalide();
    }

    $anilistIdPost = (int)($_POST['anilist_id'] ?? 0);
    $titrePost = trim($_POST['titre'] ?? '');
        $genresPost = traduireGenres(trim($_POST['genres'] ?? ''));
        $descriptionPost = descriptionFrancaise($_POST['description'] ?? '');
    $imagePost = trim($_POST['image_url'] ?? '');

    if ($anilistIdPost > 0 && $titrePost !== '') {
        $reqExiste = $pdo->prepare(
            "SELECT id FROM webtoons
             WHERE id_utilisateur = ? AND (anilist_id = ? OR titre = ?)
             LIMIT 1"
        );
        $reqExiste->execute([$userId, $anilistIdPost, $titrePost]);

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
                $anilistIdPost,
                $titrePost,
                'AniList',
                $genresPost,
                $descriptionPost,
                $imagePost
            ]);

            header('Location: detail_webtoon.php?id=' . $anilistIdPost . '&ajout=ok');
            exit;
        }
    } else {
        $erreur = t('add_error');
    }
}

if (isset($_GET['ajout'])) {
    $succes = t('added_to_library');
}

$webtoon = appelerAnilistDetail($idAnilist, $erreurAnilist);

$titre_page = t('details');
include 'includes/header.php';
?>

<?php if ($erreur): ?>
    <div class="alerte alerte-erreur"><?= htmlspecialchars($erreur) ?></div>
<?php endif; ?>

<?php if ($succes): ?>
    <div class="alerte alerte-succes"><?= htmlspecialchars($succes) ?></div>
<?php endif; ?>

<?php if ($erreurAnilist || !$webtoon): ?>
    <div class="message-vide">
        <img src="<?= $base ?>/assets/img/icon-empty.svg" alt="" class="icone-vide-svg">
        <p><?= htmlspecialchars($erreurAnilist ?: (langueCourante() === 'fr' ? "Impossible de charger ce webtoon depuis AniList." : 'Unable to load this webtoon from AniList.')) ?></p>
        <a href="rechercher.php" class="btn btn-vert"><?= htmlspecialchars(t('back')) ?></a>
    </div>
<?php else: ?>
    <?php
        $titre = titreDetailAnilist($webtoon);
        $titreOriginal = $webtoon['title']['native'] ?? '';
        $titreRomaji = $webtoon['title']['romaji'] ?? '';
        $description = nettoyerDescriptionDetail($webtoon['description'] ?? '');
        $genres = traduireGenres($webtoon['genres'] ?? []);
        $image = $webtoon['coverImage']['large'] ?? '';
        $banniere = $webtoon['bannerImage'] ?? '';
        $siteUrl = $webtoon['siteUrl'] ?? '';

        $reqExiste = $pdo->prepare(
            "SELECT * FROM webtoons
             WHERE id_utilisateur = ? AND (anilist_id = ? OR titre = ?)
             LIMIT 1"
        );
        $reqExiste->execute([$userId, (int)$webtoon['id'], $titre]);
        $suiviPerso = $reqExiste->fetch();
        $dejaAjoute = (bool)$suiviPerso;
    ?>

    <section class="detail-hero <?= empty($banniere) ? 'sans-banniere' : '' ?>"
             <?php if (!empty($banniere)): ?>
                 style="background-image: url('<?= htmlspecialchars($banniere) ?>');"
             <?php endif; ?>>
        <div class="detail-hero-overlay"></div>
    </section>

    <section class="detail-layout">
        <aside class="detail-sidebar">
            <?php if (!empty($image)): ?>
                <img class="detail-cover" src="<?= htmlspecialchars($image) ?>" alt="<?= htmlspecialchars($titre) ?>">
            <?php else: ?>
                <div class="detail-cover detail-cover-placeholder">
                    <img src="<?= $base ?>/assets/img/icon-book.svg" alt="">
                </div>
            <?php endif; ?>

            <div class="detail-actions">
                <?php if ($dejaAjoute): ?>
                    <button type="button" class="btn btn-gris btn-desactive" disabled><?= htmlspecialchars(t('already_added')) ?></button>
                    <a href="modifier_webtoon.php?id=<?= (int)$suiviPerso['id'] ?>" class="btn btn-vert"><?= htmlspecialchars(t('edit_progress')) ?></a>
                <?php else: ?>
                    <form method="POST" action="detail_webtoon.php?id=<?= (int)$webtoon['id'] ?>">
                        <?= champCsrf() ?>
                        <input type="hidden" name="anilist_id" value="<?= (int)$webtoon['id'] ?>">
                        <input type="hidden" name="titre" value="<?= htmlspecialchars($titre) ?>">
                        <input type="hidden" name="genres" value="<?= htmlspecialchars($genres) ?>">
                        <input type="hidden" name="description" value="<?= htmlspecialchars($description) ?>">
                        <input type="hidden" name="image_url" value="<?= htmlspecialchars($image) ?>">
                        <button type="submit" class="btn btn-vert"><?= htmlspecialchars(t('add')) ?></button>
                    </form>
                <?php endif; ?>

                <?php if (!empty($siteUrl)): ?>
                    <a href="<?= htmlspecialchars($siteUrl) ?>" target="_blank" rel="noopener" class="btn btn-gris"><?= htmlspecialchars(t('see_anilist')) ?></a>
                <?php endif; ?>
            </div>

            <div class="detail-info-card">
                <h2><?= htmlspecialchars(t('information')) ?></h2>
                <dl>
                    <div><dt><?= htmlspecialchars(t('status')) ?></dt><dd><?= htmlspecialchars(statutDetailFrancais($webtoon['status'] ?? '')) ?></dd></div>
                    <div><dt><?= htmlspecialchars(t('chapters')) ?></dt><dd><?= valeurDetail($webtoon['chapters'] ?? null) ?></dd></div>
                    <div><dt><?= htmlspecialchars(t('volumes')) ?></dt><dd><?= valeurDetail($webtoon['volumes'] ?? null) ?></dd></div>
                    <div><dt><?= htmlspecialchars(t('average_score')) ?></dt><dd><?= valeurDetail($webtoon['averageScore'] ?? null, '/100') ?></dd></div>
                    <div><dt><?= htmlspecialchars(t('mean_score')) ?></dt><dd><?= valeurDetail($webtoon['meanScore'] ?? null, '/100') ?></dd></div>
                    <div><dt><?= htmlspecialchars(t('popularity')) ?></dt><dd><?= valeurDetail($webtoon['popularity'] ?? null) ?></dd></div>
                    <div><dt><?= htmlspecialchars(t('favourites')) ?></dt><dd><?= valeurDetail($webtoon['favourites'] ?? null) ?></dd></div>
                    <div><dt><?= htmlspecialchars(t('country')) ?></dt><dd><?= htmlspecialchars(paysDetailFrancais($webtoon['countryOfOrigin'] ?? '')) ?></dd></div>
                    <div><dt><?= htmlspecialchars(t('start_date')) ?></dt><dd><?= htmlspecialchars(dateDetailFrancais($webtoon['startDate'] ?? [])) ?></dd></div>
                    <div><dt><?= htmlspecialchars(t('end_date')) ?></dt><dd><?= htmlspecialchars(dateDetailFrancais($webtoon['endDate'] ?? [])) ?></dd></div>
                    <div><dt><?= htmlspecialchars(t('genres')) ?></dt><dd><?= htmlspecialchars($genres ?: t('not_filled')) ?></dd></div>
                </dl>
            </div>
        </aside>

        <article class="detail-main">
            <div class="detail-title-zone">
                <a href="javascript:history.back()" class="lien-retour"><?= htmlspecialchars(t('back')) ?></a>
                <h1><?= htmlspecialchars($titre) ?></h1>
                <?php if (!empty($titreOriginal) || !empty($titreRomaji)): ?>
                    <p>
                        <?= htmlspecialchars($titreRomaji) ?>
                        <?= !empty($titreOriginal) ? ' / ' . htmlspecialchars($titreOriginal) : '' ?>
                    </p>
                <?php endif; ?>
            </div>

            <section class="detail-section">
                <h2><?= htmlspecialchars(t('summary')) ?></h2>
                <p><?= nl2br(htmlspecialchars($description)) ?></p>
            </section>

            <?php if ($dejaAjoute): ?>
                <section class="detail-section suivi-personnel-detail">
                    <div class="entete-suivi-detail">
                        <h2><?= htmlspecialchars(t('my_progress')) ?></h2>
                        <a href="modifier_webtoon.php?id=<?= (int)$suiviPerso['id'] ?>" class="btn btn-vert btn-carte"><?= htmlspecialchars(t('edit_progress')) ?></a>
                    </div>

                    <div class="detail-info-grid">
                        <div><strong><?= htmlspecialchars(t('status')) ?></strong><span><?= htmlspecialchars(statutSuiviFrancais($suiviPerso['statut'] ?? 'a_lire')) ?></span></div>
                        <div><strong><?= htmlspecialchars(t('chapter_current')) ?></strong><span><?= (int)($suiviPerso['chapitre_actuel'] ?? 0) ?></span></div>
                        <div><strong><?= htmlspecialchars(t('personal_score')) ?></strong><span><?= !is_null($suiviPerso['note']) ? (int)$suiviPerso['note'] . '/10' : t('not_rated') ?></span></div>
                        <div><strong><?= htmlspecialchars(t('intention')) ?></strong><span><?= htmlspecialchars(intentionSuiviFrancais($suiviPerso['intention'] ?? 'hesite')) ?></span></div>
                    </div>

                    <?php if (!empty($suiviPerso['commentaire'])): ?>
                        <p class="commentaire-personnel"><?= nl2br(htmlspecialchars($suiviPerso['commentaire'])) ?></p>
                    <?php endif; ?>
                </section>
            <?php endif; ?>

            <section class="detail-section">
                <h2><?= htmlspecialchars(t('information')) ?></h2>
                <div class="detail-info-grid">
                    <div><strong><?= htmlspecialchars(t('status')) ?></strong><span><?= htmlspecialchars(statutDetailFrancais($webtoon['status'] ?? '')) ?></span></div>
                    <div><strong><?= htmlspecialchars(t('chapters')) ?></strong><span><?= valeurDetail($webtoon['chapters'] ?? null) ?></span></div>
                    <div><strong><?= htmlspecialchars(t('volumes')) ?></strong><span><?= valeurDetail($webtoon['volumes'] ?? null) ?></span></div>
                    <div><strong><?= htmlspecialchars(t('score')) ?></strong><span><?= valeurDetail($webtoon['averageScore'] ?? null, '/100') ?></span></div>
                    <div><strong><?= htmlspecialchars(t('popularity')) ?></strong><span><?= valeurDetail($webtoon['popularity'] ?? null) ?></span></div>
                    <div><strong><?= htmlspecialchars(t('country')) ?></strong><span><?= htmlspecialchars(paysDetailFrancais($webtoon['countryOfOrigin'] ?? '')) ?></span></div>
                </div>
            </section>

            <?php if (!empty($webtoon['characters']['nodes'])): ?>
                <section class="detail-section">
                    <h2><?= htmlspecialchars(t('characters')) ?></h2>
                    <div class="detail-mini-grid">
                        <?php foreach ($webtoon['characters']['nodes'] as $personnage): ?>
                            <?php petiteCarteDetail($personnage['name']['full'] ?? t('characters'), $personnage['image']['medium'] ?? ''); ?>
                        <?php endforeach; ?>
                    </div>
                </section>
            <?php endif; ?>

            <?php if (!empty($webtoon['staff']['nodes'])): ?>
                <section class="detail-section">
                    <h2><?= htmlspecialchars(t('staff')) ?></h2>
                    <div class="detail-mini-grid">
                        <?php foreach ($webtoon['staff']['nodes'] as $staff): ?>
                            <?php petiteCarteDetail($staff['name']['full'] ?? 'Staff', $staff['image']['medium'] ?? ''); ?>
                        <?php endforeach; ?>
                    </div>
                </section>
            <?php endif; ?>

            <?php if (!empty($webtoon['relations']['edges'])): ?>
                <section class="detail-section">
                    <h2><?= htmlspecialchars(t('related')) ?></h2>
                    <div class="detail-mini-grid">
                        <?php foreach (array_slice($webtoon['relations']['edges'], 0, 8) as $relation): ?>
                            <?php
                                $mediaLie = $relation['node'] ?? [];
                                $titreLie = titreDetailAnilist($mediaLie);
                                $imageLie = $mediaLie['coverImage']['medium'] ?? '';
                                $lienLie = !empty($mediaLie['id']) ? 'detail_webtoon.php?id=' . (int)$mediaLie['id'] : '';
                                petiteCarteDetail($titreLie, $imageLie, $lienLie);
                            ?>
                        <?php endforeach; ?>
                    </div>
                </section>
            <?php endif; ?>
        </article>
    </section>
<?php endif; ?>

<?php include 'includes/footer.php'; ?>
