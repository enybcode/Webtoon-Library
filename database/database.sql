-- =============================================
-- BASE DE DONNEES : webtoon_library
-- Projet BTS SIO SLAM - Gestion de Webtoons
-- =============================================

CREATE DATABASE IF NOT EXISTS webtoon_library CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE webtoon_library;

-- =============================================
-- TABLE : utilisateurs
-- =============================================
CREATE TABLE IF NOT EXISTS utilisateurs (
    id              INT AUTO_INCREMENT PRIMARY KEY,
    email           VARCHAR(255) NOT NULL UNIQUE,
    pseudo          VARCHAR(100) NOT NULL UNIQUE,
    mot_de_passe    VARCHAR(255) NOT NULL,
    langue          VARCHAR(5) NOT NULL DEFAULT 'en',
    is_admin        TINYINT NOT NULL DEFAULT 0,
    date_creation   DATETIME DEFAULT CURRENT_TIMESTAMP
);

-- =============================================
-- TABLE : webtoons
-- Stocke les oeuvres et le suivi personnel de chaque utilisateur
-- =============================================
CREATE TABLE IF NOT EXISTS webtoons (
    id                  INT AUTO_INCREMENT PRIMARY KEY,
    id_utilisateur      INT NOT NULL,
    anilist_id          INT NULL,
    titre               VARCHAR(255) NOT NULL,
    auteur              VARCHAR(255),
    genre               VARCHAR(100),
    description         TEXT,
    statut              ENUM('a_lire', 'en_cours', 'en_pause', 'termine', 'abandonne') DEFAULT 'a_lire',
    chapitre_actuel     INT DEFAULT 0,
    note                TINYINT CHECK (note BETWEEN 0 AND 10),
    commentaire         TEXT,
    intention           ENUM('continuer', 'hesite', 'arreter') DEFAULT 'hesite',
    image_url           VARCHAR(500),
    date_ajout          DATETIME DEFAULT CURRENT_TIMESTAMP,
    date_modification   DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    FOREIGN KEY (id_utilisateur) REFERENCES utilisateurs(id) ON DELETE CASCADE
);

-- =============================================
-- TABLE : traductions_cache
-- Evite de rappeler DeepL pour un texte deja traduit
-- =============================================
CREATE TABLE IF NOT EXISTS traductions_cache (
    id              INT AUTO_INCREMENT PRIMARY KEY,
    texte_hash      VARCHAR(64) NOT NULL,
    texte_original  TEXT NOT NULL,
    langue_cible    VARCHAR(10) NOT NULL,
    texte_traduit   TEXT NOT NULL,
    date_creation   DATETIME DEFAULT CURRENT_TIMESTAMP,

    UNIQUE KEY unique_traduction (texte_hash, langue_cible)
);

-- =============================================
-- TABLE : categories_admin
-- Categories modifiables depuis l'espace admin
-- =============================================
CREATE TABLE IF NOT EXISTS categories_admin (
    id                  INT AUTO_INCREMENT PRIMARY KEY,
    nom_anilist         VARCHAR(100) NOT NULL UNIQUE,
    label_fr            VARCHAR(100) NOT NULL,
    label_en            VARCHAR(100) NOT NULL,
    actif               TINYINT NOT NULL DEFAULT 1,
    adulte              TINYINT NOT NULL DEFAULT 1,
    ordre_affichage     INT NOT NULL DEFAULT 0
);

-- =============================================
-- DONNEES DE TEST
-- Mot de passe : "password123" hash avec password_hash
-- =============================================
INSERT INTO utilisateurs (email, pseudo, mot_de_passe) VALUES
('test@example.com', 'TestUser', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi');

INSERT INTO webtoons
(id_utilisateur, anilist_id, titre, auteur, genre, description, statut, chapitre_actuel, note, commentaire, intention, image_url)
VALUES
(1, 105398, 'Solo Leveling', 'Chugong', 'Action / Fantasy', 'Un chasseur de rang E devient le chasseur le plus puissant du monde.', 'termine', 179, 9, 'Lecture terminee, tres bonne progression.', 'continuer', 'https://upload.wikimedia.org/wikipedia/en/3/3f/Solo_Leveling_manhwa_cover.jpg'),
(1, NULL, 'Tower of God', 'SIU', 'Aventure / Mystere', 'Un garcon entre dans une tour mysterieuse pour retrouver son amie.', 'en_cours', 580, 8, NULL, 'continuer', 'https://upload.wikimedia.org/wikipedia/en/2/2b/Tower_of_God_cover.jpg'),
(1, NULL, 'The Beginning After the End', 'TurtleMe', 'Fantasy / Isekai', 'Un roi reincarne dans un monde de magie.', 'a_lire', 0, NULL, NULL, 'hesite', NULL);
