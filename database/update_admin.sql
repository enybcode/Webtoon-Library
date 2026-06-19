-- =============================================
-- Mise a jour espace admin + categories
-- A executer sans supprimer les donnees existantes
-- =============================================

USE webtoon_library;

ALTER TABLE utilisateurs
    ADD COLUMN IF NOT EXISTS is_admin TINYINT NOT NULL DEFAULT 0 AFTER langue;

CREATE TABLE IF NOT EXISTS categories_admin (
    id                  INT AUTO_INCREMENT PRIMARY KEY,
    nom_anilist         VARCHAR(100) NOT NULL UNIQUE,
    label_fr            VARCHAR(100) NOT NULL,
    label_en            VARCHAR(100) NOT NULL,
    actif               TINYINT NOT NULL DEFAULT 1,
    adulte              TINYINT NOT NULL DEFAULT 1,
    ordre_affichage     INT NOT NULL DEFAULT 0
);

INSERT IGNORE INTO categories_admin
(nom_anilist, label_fr, label_en, actif, adulte, ordre_affichage)
VALUES
('Action', 'Action', 'Action', 1, 1, 10),
('Fantasy', 'Fantasy', 'Fantasy', 1, 1, 20),
('Romance', 'Romance', 'Romance', 1, 1, 30),
('Drama', 'Drame', 'Drama', 1, 1, 40),
('Comedy', 'Comedie', 'Comedy', 1, 1, 50),
('Horror', 'Horreur', 'Horror', 1, 1, 60),
('Adventure', 'Aventure', 'Adventure', 1, 1, 70),
('Mystery', 'Mystere', 'Mystery', 1, 1, 80),
('Sports', 'Sport', 'Sports', 1, 0, 90),
('Sci-Fi', 'Science-fiction', 'Sci-Fi', 1, 1, 100),
('Slice of Life', 'Tranche de vie', 'Slice of Life', 1, 1, 110),
('Supernatural', 'Surnaturel', 'Supernatural', 1, 1, 120),
('Thriller', 'Thriller', 'Thriller', 1, 1, 130),
('Psychological', 'Psychologique', 'Psychological', 1, 1, 140);
