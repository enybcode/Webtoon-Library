<?php
// =============================================
// deepl_config.php - Configuration DeepL cote serveur
// =============================================

// Mets ta cle DeepL ici ou dans une variable d'environnement DEEPL_API_KEY.
if (!defined('DEEPL_API_KEY')) {
    define('DEEPL_API_KEY', getenv('DEEPL_API_KEY') ?: '');
}

if (!defined('DEEPL_API_URL')) {
    define('DEEPL_API_URL', 'https://api-free.deepl.com/v2/translate');
}
?>
