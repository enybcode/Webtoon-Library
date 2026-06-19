-- =============================================
-- Mise a jour multilingue + cache DeepL
-- A executer dans phpMyAdmin sur la base webtoon_library
-- =============================================

USE webtoon_library;

ALTER TABLE utilisateurs
    ADD COLUMN IF NOT EXISTS langue VARCHAR(5) NOT NULL DEFAULT 'en' AFTER mot_de_passe;

UPDATE utilisateurs
SET langue = 'en'
WHERE langue IS NULL OR langue = '';

CREATE TABLE IF NOT EXISTS traductions_cache (
    id INT AUTO_INCREMENT PRIMARY KEY,
    texte_hash VARCHAR(64) NOT NULL,
    texte_original TEXT NOT NULL,
    langue_cible VARCHAR(10) NOT NULL,
    texte_traduit TEXT NOT NULL,
    date_creation DATETIME DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY unique_traduction (texte_hash, langue_cible)
);
