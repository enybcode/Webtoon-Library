<?php
// =============================================
// security.php - Petites protections communes
// =============================================

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

function securiserSessionHeaders()
{
    if (headers_sent()) {
        return;
    }

    header('X-Frame-Options: SAMEORIGIN');
    header('X-Content-Type-Options: nosniff');
    header('Referrer-Policy: same-origin');
    header('Permissions-Policy: geolocation=(), microphone=(), camera=()');
}

function csrfToken()
{
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }

    return $_SESSION['csrf_token'];
}

function verifierCsrf()
{
    $tokenSession = $_SESSION['csrf_token'] ?? '';
    $tokenPost = $_POST['csrf_token'] ?? '';

    return $tokenSession !== '' && hash_equals($tokenSession, $tokenPost);
}

function champCsrf()
{
    return '<input type="hidden" name="csrf_token" value="' .
        htmlspecialchars(csrfToken(), ENT_QUOTES, 'UTF-8') .
        '">';
}

function refuserRequeteInvalide()
{
    http_response_code(400);
    exit('Requete invalide.');
}
