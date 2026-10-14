<?php
$path = rawurldecode(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH) ?? '/');
if (preg_match('~(?:^|/)(?:database|includes|data|logs|runtime)(?:/|$)|(?:^|/)\.|\.(?:sql|sqlite|sqlite-wal|sqlite-shm|env|md|ps1|cmd|log|ini)$~i', $path)) {
    http_response_code(404);
    exit('Introuvable');
}
return false;
