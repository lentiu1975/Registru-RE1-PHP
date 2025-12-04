<?php
/**
 * Redirect către noul panou admin
 */
session_start();
require_once 'config/database.php';
require_once 'includes/functions.php';

// Verifică autentificare
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

// Verifică dacă utilizatorul este admin
$currentUser = dbFetchOne("SELECT * FROM users WHERE id = ? AND is_active = 1", [$_SESSION['user_id']]);

if (!$currentUser) {
    session_destroy();
    header('Location: login.php');
    exit;
}

// Dacă este admin, redirecționează către admin_new.php
if ($currentUser['is_admin']) {
    header('Location: admin_new.php');
    exit;
}

// Dacă nu este admin, redirecționează către pagina principală
header('Location: index.php');
exit;
