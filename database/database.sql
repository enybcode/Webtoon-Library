-- =============================================
-- BASE DE DONNÉES : webtoon_app
-- Projet BTS SIO SLAM - Gestion de Webtoons
-- =============================================

-- On crée la base de données si elle n'existe pas
CREATE DATABASE IF NOT EXISTS webtoon_app CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

-- On sélectionne la base de données
USE webtoon_app;

-- =============================================
-- TABLE : utilisateurs
-- Stocke les comptes des utilisateurs
-- =============================================
CREATE TABLE IF NOT EXISTS utilisateurs (
    id              INT AUTO_INCREMENT PRIMARY KEY,  -- Identifiant unique
    email           VARCHAR(255) NOT NULL UNIQUE,    -- Email (unique)
    pseudo          VARCHAR(100) NOT NULL UNIQUE,    -- Pseudo (unique)
    mot_de_passe    VARCHAR(255) NOT NULL,           -- Mot de passe hashé
    date_creation   DATETIME DEFAULT CURRENT_TIMESTAMP -- Date d'inscription
);

-- =============================================
-- TABLE : webtoons
-- Stocke les webtoons de chaque utilisateur
-- =============================================
CREATE TABLE IF NOT EXISTS webtoons (
    id              INT AUTO_INCREMENT PRIMARY KEY,
    id_utilisateur  INT NOT NULL,                    -- Lien vers l'utilisateur
    titre           VARCHAR(255) NOT NULL,
    auteur          VARCHAR(255),
    genre           VARCHAR(100),
    description     TEXT,
    statut          ENUM('a_lire', 'en_cours', 'termine') DEFAULT 'a_lire',
    chapitre_actuel INT DEFAULT 0,
    note            TINYINT CHECK (note BETWEEN 0 AND 10), -- Note de 0 à 10
    image_url       VARCHAR(500),
    date_ajout      DATETIME DEFAULT CURRENT_TIMESTAMP,

    -- Clé étrangère : si l'utilisateur est supprimé, ses webtoons le sont aussi
    FOREIGN KEY (id_utilisateur) REFERENCES utilisateurs(id) ON DELETE CASCADE
);

-- =============================================
-- DONNÉES DE TEST (optionnel)
-- Mot de passe : "password123" hashé avec password_hash
-- =============================================

-- Utilisateur de test
INSERT INTO utilisateurs (email, pseudo, mot_de_passe) VALUES
('test@example.com', 'TestUser', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi');

-- Quelques webtoons de test pour cet utilisateur (id = 1)
INSERT INTO webtoons (id_utilisateur, titre, auteur, genre, description, statut, chapitre_actuel, note, image_url) VALUES
(1, 'Solo Leveling', 'Chugong', 'Action / Fantasy', 'Un chasseur de rang E devient le chasseur le plus puissant du monde.', 'termine', 179, 9, 'https://upload.wikimedia.org/wikipedia/en/3/3f/Solo_Leveling_manhwa_cover.jpg'),
(1, 'Tower of God', 'SIU', 'Adventure / Mystery', 'Un garçon entre dans une tour mystérieuse pour retrouver son amie.', 'en_cours', 580, 8, 'https://upload.wikimedia.org/wikipedia/en/2/2b/Tower_of_God_cover.jpg'),
(1, 'The Beginning After the End', 'TurtleMe', 'Fantasy / Isekai', 'Un roi réincarné dans un monde de magie.', 'a_lire', 0, NULL, NULL);
