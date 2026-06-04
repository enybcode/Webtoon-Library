<?php
// =============================================
// detail_webtoon.php - Detail complet AniList
// =============================================

session_start();
include 'includes/config.php';

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
        $erreurApi = "L'extension PHP cURL n'est pas activee. AniList ne peut pas etre interroge.";
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
        $erreurApi = "AniList ne repond pas pour le moment : " . curl_error($curl);
        curl_close($curl);
        return null;
    }

    curl_close($curl);

    if ($httpCode !== 200) {
        $erreurApi = "AniList a retourne une erreur HTTP " . $httpCode . ".";
        return null;
    }

    $data = json_decode($response, true);

    if (!isset($data['data']['Media'])) {
        $erreurApi = "La reponse AniList est invalide.";
        return null;
    }

    return $data['data']['Media'];
}

function titreDetailAnilist($webtoon)
{
    return ($webtoon['title']['english'] ?? '')
        ?: (($webtoon['title']['romaji'] ?? '')
        ?: (($webtoon['title']['native'] ?? '') ?: 'Titre inconnu'));
}

function nettoyerDescriptionDetail($description)
{
    $description = html_entity_decode($description ?? '', ENT_QUOTES, 'UTF-8');
    $description = strip_tags($description);
    $description = trim(preg_replace('/\s+/', ' ', $description));

    $remplacements = [
        'A story about' => 'Une histoire sur',
        'This story follows' => 'Cette histoire suit',
        'The story follows' => 'L histoire suit',
        'follows' => 'suit',
        'After' => 'Apres',
        'after' => 'apres',
        'In a world' => 'Dans un monde',
        'in a world' => 'dans un monde',
        'where' => 'ou',
        'young' => 'jeune',
        'boy' => 'garcon',
        'girl' => 'fille',
        'man' => 'homme',
        'woman' => 'femme',
        'hero' => 'heros',
        'must' => 'doit',
        'fight' => 'combattre',
        'becomes' => 'devient',
        'discovers' => 'decouvre',
        'secret' => 'secret',
        'power' => 'pouvoir',
        'magic' => 'magie',
        'school' => 'ecole',
        'family' => 'famille',
        'friend' => 'ami',
        'friends' => 'amis',
        'love' => 'amour',
        'life' => 'vie',
        'death' => 'mort',
        'world' => 'monde',
        'adventure' => 'aventure',
        'adventures' => 'aventures'
    ];

    $description = str_replace(array_keys($remplacements), array_values($remplacements), $description);

    return $description !== '' ? $description : 'Aucun resume disponible pour ce webtoon.';
}

function statutDetailFrancais($statut)
{
    $statuts = [
        'RELEASING' => 'En cours',
        'FINISHED' => 'Termine',
        'HIATUS' => 'En pause',
        'CANCELLED' => 'Annule',
        'NOT_YET_RELEASED' => 'Pas encore sorti'
    ];

    return $statuts[$statut] ?? 'Non renseigne';
}

function paysDetailFrancais($pays)
{
    $paysTraduits = [
        'KR' => 'Coree',
        'JP' => 'Japon',
        'CN' => 'Chine'
    ];

    return $paysTraduits[$pays] ?? ($pays ?: 'Non renseigne');
}

function dateDetailFrancais($date)
{
    if (empty($date['year'])) {
        return 'Non renseignee';
    }

    $mois = !empty($date['month']) ? str_pad((string)$date['month'], 2, '0', STR_PAD_LEFT) : '??';
    $jour = !empty($date['day']) ? str_pad((string)$date['day'], 2, '0', STR_PAD_LEFT) : '??';

    return $jour . '/' . $mois . '/' . $date['year'];
}

function valeurDetail($valeur, $suffixe = '')
{
    if ($valeur === null || $valeur === '') {
        return 'Non renseigne';
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

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $anilistIdPost = (int)($_POST['anilist_id'] ?? 0);
    $titrePost = trim($_POST['titre'] ?? '');
    $genresPost = trim($_POST['genres'] ?? '');
    $descriptionPost = trim($_POST['description'] ?? '');
    $imagePost = trim($_POST['image_url'] ?? '');

    if ($anilistIdPost > 0 && $titrePost !== '') {
        $reqExiste = $pdo->prepare(
            "SELECT id FROM webtoons WHERE id_utilisateur = ? AND titre = ? LIMIT 1"
        );
        $reqExiste->execute([$userId, $titrePost]);

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
        $erreur = "Impossible d'ajouter ce webtoon.";
    }
}

if (isset($_GET['ajout'])) {
    $succes = "Webtoon ajoute a votre bibliotheque.";
}

$webtoon = appelerAnilistDetail($idAnilist, $erreurAnilist);

$titre_page = "Detail webtoon";
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
        <p><?= htmlspecialchars($erreurAnilist ?: "Impossible de charger ce webtoon depuis AniList.") ?></p>
        <a href="rechercher.php" class="btn btn-vert">Retour a la recherche</a>
    </div>
<?php else: ?>
    <?php
        $titre = titreDetailAnilist($webtoon);
        $titreOriginal = $webtoon['title']['native'] ?? '';
        $titreRomaji = $webtoon['title']['romaji'] ?? '';
        $description = nettoyerDescriptionDetail($webtoon['description'] ?? '');
        $genres = implode(', ', $webtoon['genres'] ?? []);
        $image = $webtoon['coverImage']['large'] ?? '';
        $banniere = $webtoon['bannerImage'] ?? '';
        $siteUrl = $webtoon['siteUrl'] ?? '';

        $reqExiste = $pdo->prepare(
            "SELECT id FROM webtoons WHERE id_utilisateur = ? AND titre = ? LIMIT 1"
        );
        $reqExiste->execute([$userId, $titre]);
        $dejaAjoute = (bool)$reqExiste->fetch();
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
                    <button type="button" class="btn btn-gris btn-desactive" disabled>Deja ajoute</button>
                <?php else: ?>
                    <form method="POST" action="detail_webtoon.php?id=<?= (int)$webtoon['id'] ?>">
                        <input type="hidden" name="anilist_id" value="<?= (int)$webtoon['id'] ?>">
                        <input type="hidden" name="titre" value="<?= htmlspecialchars($titre) ?>">
                        <input type="hidden" name="genres" value="<?= htmlspecialchars($genres) ?>">
                        <input type="hidden" name="description" value="<?= htmlspecialchars($description) ?>">
                        <input type="hidden" name="image_url" value="<?= htmlspecialchars($image) ?>">
                        <button type="submit" class="btn btn-vert">Ajouter a ma liste</button>
                    </form>
                <?php endif; ?>

                <?php if (!empty($siteUrl)): ?>
                    <a href="<?= htmlspecialchars($siteUrl) ?>" target="_blank" rel="noopener" class="btn btn-gris">Voir sur AniList</a>
                <?php endif; ?>
            </div>

            <div class="detail-info-card">
                <h2>Informations</h2>
                <dl>
                    <div><dt>Statut</dt><dd><?= htmlspecialchars(statutDetailFrancais($webtoon['status'] ?? '')) ?></dd></div>
                    <div><dt>Chapitres</dt><dd><?= valeurDetail($webtoon['chapters'] ?? null) ?></dd></div>
                    <div><dt>Volumes</dt><dd><?= valeurDetail($webtoon['volumes'] ?? null) ?></dd></div>
                    <div><dt>Note moyenne</dt><dd><?= valeurDetail($webtoon['averageScore'] ?? null, '/100') ?></dd></div>
                    <div><dt>Score moyen</dt><dd><?= valeurDetail($webtoon['meanScore'] ?? null, '/100') ?></dd></div>
                    <div><dt>Popularite</dt><dd><?= valeurDetail($webtoon['popularity'] ?? null) ?></dd></div>
                    <div><dt>Favoris</dt><dd><?= valeurDetail($webtoon['favourites'] ?? null) ?></dd></div>
                    <div><dt>Pays</dt><dd><?= htmlspecialchars(paysDetailFrancais($webtoon['countryOfOrigin'] ?? '')) ?></dd></div>
                    <div><dt>Debut</dt><dd><?= htmlspecialchars(dateDetailFrancais($webtoon['startDate'] ?? [])) ?></dd></div>
                    <div><dt>Fin</dt><dd><?= htmlspecialchars(dateDetailFrancais($webtoon['endDate'] ?? [])) ?></dd></div>
                    <div><dt>Genres</dt><dd><?= htmlspecialchars($genres ?: 'Non renseigne') ?></dd></div>
                </dl>
            </div>
        </aside>

        <article class="detail-main">
            <div class="detail-title-zone">
                <a href="javascript:history.back()" class="lien-retour">Retour</a>
                <h1><?= htmlspecialchars($titre) ?></h1>
                <?php if (!empty($titreOriginal) || !empty($titreRomaji)): ?>
                    <p>
                        <?= htmlspecialchars($titreRomaji) ?>
                        <?= !empty($titreOriginal) ? ' / ' . htmlspecialchars($titreOriginal) : '' ?>
                    </p>
                <?php endif; ?>
            </div>

            <section class="detail-section">
                <h2>Resume</h2>
                <p><?= nl2br(htmlspecialchars($description)) ?></p>
            </section>

            <section class="detail-section">
                <h2>Informations</h2>
                <div class="detail-info-grid">
                    <div><strong>Statut</strong><span><?= htmlspecialchars(statutDetailFrancais($webtoon['status'] ?? '')) ?></span></div>
                    <div><strong>Chapitres</strong><span><?= valeurDetail($webtoon['chapters'] ?? null) ?></span></div>
                    <div><strong>Volumes</strong><span><?= valeurDetail($webtoon['volumes'] ?? null) ?></span></div>
                    <div><strong>Note</strong><span><?= valeurDetail($webtoon['averageScore'] ?? null, '/100') ?></span></div>
                    <div><strong>Popularite</strong><span><?= valeurDetail($webtoon['popularity'] ?? null) ?></span></div>
                    <div><strong>Pays</strong><span><?= htmlspecialchars(paysDetailFrancais($webtoon['countryOfOrigin'] ?? '')) ?></span></div>
                </div>
            </section>

            <?php if (!empty($webtoon['characters']['nodes'])): ?>
                <section class="detail-section">
                    <h2>Personnages</h2>
                    <div class="detail-mini-grid">
                        <?php foreach ($webtoon['characters']['nodes'] as $personnage): ?>
                            <?php petiteCarteDetail($personnage['name']['full'] ?? 'Personnage', $personnage['image']['medium'] ?? ''); ?>
                        <?php endforeach; ?>
                    </div>
                </section>
            <?php endif; ?>

            <?php if (!empty($webtoon['staff']['nodes'])): ?>
                <section class="detail-section">
                    <h2>Staff</h2>
                    <div class="detail-mini-grid">
                        <?php foreach ($webtoon['staff']['nodes'] as $staff): ?>
                            <?php petiteCarteDetail($staff['name']['full'] ?? 'Staff', $staff['image']['medium'] ?? ''); ?>
                        <?php endforeach; ?>
                    </div>
                </section>
            <?php endif; ?>

            <?php if (!empty($webtoon['relations']['edges'])): ?>
                <section class="detail-section">
                    <h2>Oeuvres liees</h2>
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
