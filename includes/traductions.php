<?php
// =============================================
// traductions.php - Traductions AniList + DeepL
// =============================================

include_once __DIR__ . '/lang.php';
include_once __DIR__ . '/deepl_config.php';

function traduireStatut($statut)
{
    if (langueCourante() === 'en') {
        $statutsEn = [
            'RELEASING' => 'Releasing',
            'FINISHED' => 'Finished',
            'HIATUS' => 'Hiatus',
            'CANCELLED' => 'Cancelled',
            'NOT_YET_RELEASED' => 'Not yet released',
            'a_lire' => 'To read',
            'en_cours' => 'Reading',
            'en_pause' => 'Paused',
            'termine' => 'Finished',
            'abandonne' => 'Dropped'
        ];

        return $statutsEn[$statut] ?? 'Unknown';
    }

    $statutsFr = [
        'RELEASING' => 'En cours',
        'FINISHED' => 'TerminÃƒÂ©',
        'HIATUS' => 'En pause',
        'CANCELLED' => 'AnnulÃƒÂ©',
        'NOT_YET_RELEASED' => 'Pas encore sorti',
        'a_lire' => 'Ãƒâ‚¬ lire',
        'en_cours' => 'En cours',
        'en_pause' => 'En pause',
        'termine' => 'TerminÃƒÂ©',
        'abandonne' => 'AbandonnÃƒÂ©'
    ];

    return $statutsFr[$statut] ?? 'Non renseignÃƒÂ©';
}

function traduireGenre($genre)
{
    if (langueCourante() === 'en') {
        return $genre;
    }

    $genres = [
        'Action' => 'Action',
        'Adventure' => 'Aventure',
        'Comedy' => 'ComÃƒÂ©die',
        'Drama' => 'Drame',
        'Fantasy' => 'Fantasy',
        'Horror' => 'Horreur',
        'Mystery' => 'MystÃƒÂ¨re',
        'Romance' => 'Romance',
        'Sci-Fi' => 'Science-fiction',
        'Slice of Life' => 'Tranche de vie',
        'Sports' => 'Sport',
        'Supernatural' => 'Surnaturel',
        'Thriller' => 'Thriller',
        'Psychological' => 'Psychologique'
    ];

    return $genres[$genre] ?? $genre;
}

function traduireGenres($genres)
{
    if (!is_array($genres)) {
        $genres = preg_split('/\s*,\s*|\s*\/\s*/', (string)$genres);
    }

    $genresTraduits = [];

    foreach ($genres as $genre) {
        $genre = trim((string)$genre);
        if ($genre !== '') {
            $genresTraduits[] = traduireGenre($genre);
        }
    }

    return implode(', ', array_unique($genresTraduits));
}

function nettoyerDescription($description)
{
    $description = html_entity_decode($description ?? '', ENT_QUOTES, 'UTF-8');
    $description = strip_tags($description);
    return trim(preg_replace('/\s+/', ' ', $description));
}

function traduireAvecDeepL($texte, $langueCible)
{
    global $pdo;

    $texte = nettoyerDescription($texte);

    if ($texte === '') {
        return '';
    }

    $langueCible = strtoupper($langueCible);
    $hash = hash('sha256', $texte . '|' . $langueCible);

    if (isset($pdo)) {
        try {
            $reqCache = $pdo->prepare(
                "SELECT texte_traduit FROM traductions_cache
                 WHERE texte_hash = ? AND langue_cible = ?
                 LIMIT 1"
            );
            $reqCache->execute([$hash, $langueCible]);
            $cache = $reqCache->fetch();

            if ($cache) {
                return $cache['texte_traduit'];
            }
        } catch (PDOException $e) {
            // Le cache devient actif apres execution de database/update_langues.sql.
        }
    }

    if (DEEPL_API_KEY === '') {
        return '';
    }

    $curl = curl_init(DEEPL_API_URL);
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($curl, CURLOPT_POST, true);
    curl_setopt($curl, CURLOPT_POSTFIELDS, http_build_query([
        'auth_key' => DEEPL_API_KEY,
        'text' => $texte,
        'target_lang' => $langueCible
    ]));
    curl_setopt($curl, CURLOPT_TIMEOUT, 12);

    $response = curl_exec($curl);
    $httpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);

    if ($response === false || $httpCode !== 200) {
        return '';
    }

    $data = json_decode($response, true);
    $traduction = $data['translations'][0]['text'] ?? '';

    if ($traduction !== '' && isset($pdo)) {
        try {
            $insertCache = $pdo->prepare(
                "INSERT INTO traductions_cache
                 (texte_hash, texte_original, langue_cible, texte_traduit)
                 VALUES (?, ?, ?, ?)"
            );
            $insertCache->execute([$hash, $texte, $langueCible, $traduction]);
        } catch (PDOException $e) {
            // Si la table n'existe pas encore, on affiche quand meme la traduction.
        }
    }

    return $traduction;
}

function descriptionSelonLangue($description, $limite = 0)
{
    $description = nettoyerDescription($description);

    if ($description === '') {
        return langueCourante() === 'fr'
            ? 'Description franÃƒÂ§aise indisponible pour cette Ã…â€œuvre.'
            : 'Description unavailable for this work.';
    }

    if (langueCourante() === 'fr') {
        $traduction = traduireAvecDeepL($description, 'FR');
        $description = $traduction !== '' ? $traduction : 'Description franÃƒÂ§aise indisponible pour cette Ã…â€œuvre.';
    }

    if ($limite > 0 && strlen($description) > $limite) {
        $description = substr($description, 0, $limite) . '...';
    }

    return $description;
}

// Compatibilite avec les appels deja presents dans le projet.
function descriptionFrancaise($description, $limite = 0)
{
    return descriptionSelonLangue($description, $limite);
}

function formaterDateFr($date)
{
    if (empty($date['year'])) {
        return langueCourante() === 'fr' ? 'Non renseignÃƒÂ©e' : 'Unknown';
    }

    $mois = !empty($date['month']) ? str_pad((string)$date['month'], 2, '0', STR_PAD_LEFT) : '??';
    $jour = !empty($date['day']) ? str_pad((string)$date['day'], 2, '0', STR_PAD_LEFT) : '??';

    return langueCourante() === 'fr'
        ? $jour . '/' . $mois . '/' . $date['year']
        : $date['year'] . '-' . $mois . '-' . $jour;
}

function traduirePays($pays)
{
    if (langueCourante() === 'en') {
        $paysEn = [
            'KR' => 'Korea',
            'JP' => 'Japan',
            'CN' => 'China'
        ];

        return $paysEn[$pays] ?? ($pays ?: 'Unknown');
    }

    $paysFr = [
        'KR' => 'CorÃƒÂ©e',
        'JP' => 'Japon',
        'CN' => 'Chine'
    ];

    return $paysFr[$pays] ?? ($pays ?: 'Non renseignÃƒÂ©');
}

function categoriesParDefaut()
{
    return [
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
}

function chargerCategoriesAdmin($pdo, $inclureAdultes = true)
{
    try {
        $sql = "SELECT nom_anilist FROM categories_admin WHERE actif = 1";

        if (!$inclureAdultes) {
            $sql .= " AND adulte = 1";
        }

        $sql .= " ORDER BY ordre_affichage ASC, nom_anilist ASC";
        $req = $pdo->query($sql);
        $categories = [];

        foreach ($req->fetchAll() as $categorie) {
            $categories[$categorie['nom_anilist']] = $categorie['nom_anilist'];
        }

        return !empty($categories) ? $categories : categoriesParDefaut();
    } catch (PDOException $e) {
        return categoriesParDefaut();
    }
}

function labelCategorieAdmin($pdo, $nomAnilist)
{
    if ($nomAnilist === 'general') {
        return t('general');
    }

    try {
        $req = $pdo->prepare("SELECT label_fr, label_en FROM categories_admin WHERE nom_anilist = ? LIMIT 1");
        $req->execute([$nomAnilist]);
        $categorie = $req->fetch();

        if ($categorie) {
            return langueCourante() === 'fr' ? $categorie['label_fr'] : $categorie['label_en'];
        }
    } catch (PDOException $e) {
        // Table absente : fallback sur les traductions fixes.
    }

    return traduireGenre($nomAnilist);
}
?>
