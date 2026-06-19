-- =============================================
-- Mise a jour du suivi personnel des webtoons
-- A executer dans phpMyAdmin sur la base webtoon_library
-- =============================================

USE webtoon_library;

ALTER TABLE webtoons
    ADD COLUMN IF NOT EXISTS anilist_id INT NULL AFTER id_utilisateur,
    MODIFY COLUMN statut ENUM('a_lire', 'en_cours', 'en_pause', 'termine', 'abandonne') DEFAULT 'a_lire',
    ADD COLUMN IF NOT EXISTS commentaire TEXT NULL AFTER note,
    ADD COLUMN IF NOT EXISTS intention ENUM('continuer', 'hesite', 'arreter') DEFAULT 'hesite' AFTER commentaire,
    ADD COLUMN IF NOT EXISTS date_modification DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP AFTER date_ajout;
