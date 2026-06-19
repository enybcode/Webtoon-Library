-- =============================================
-- Mise a jour des donnees en francais
-- A executer dans phpMyAdmin sur la base webtoon_library
-- =============================================

USE webtoon_library;

-- Traduction simple des genres deja stockes.
UPDATE webtoons SET genre = REPLACE(genre, 'Adventure', 'Aventure') WHERE genre LIKE '%Adventure%';
UPDATE webtoons SET genre = REPLACE(genre, 'Comedy', 'Comédie') WHERE genre LIKE '%Comedy%';
UPDATE webtoons SET genre = REPLACE(genre, 'Drama', 'Drame') WHERE genre LIKE '%Drama%';
UPDATE webtoons SET genre = REPLACE(genre, 'Horror', 'Horreur') WHERE genre LIKE '%Horror%';
UPDATE webtoons SET genre = REPLACE(genre, 'Mystery', 'Mystère') WHERE genre LIKE '%Mystery%';
UPDATE webtoons SET genre = REPLACE(genre, 'Sci-Fi', 'Science-fiction') WHERE genre LIKE '%Sci-Fi%';
UPDATE webtoons SET genre = REPLACE(genre, 'Slice of Life', 'Tranche de vie') WHERE genre LIKE '%Slice of Life%';
UPDATE webtoons SET genre = REPLACE(genre, 'Sports', 'Sport') WHERE genre LIKE '%Sports%';
UPDATE webtoons SET genre = REPLACE(genre, 'Supernatural', 'Surnaturel') WHERE genre LIKE '%Supernatural%';
UPDATE webtoons SET genre = REPLACE(genre, 'Psychological', 'Psychologique') WHERE genre LIKE '%Psychological%';

-- Les descriptions vides restent propres et comprehensibles.
UPDATE webtoons
SET description = 'Description française indisponible pour cette œuvre.'
WHERE description IS NULL OR TRIM(description) = '';
