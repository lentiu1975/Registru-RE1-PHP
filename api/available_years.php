<?php
/**
 * API pentru a obține anii disponibili din database_years
 * Folosit pentru export pe an
 */

header('Content-Type: application/json; charset=utf-8');
session_start();
require_once '../config/database.php';
require_once '../includes/functions.php';

if (!isset($_SESSION['user_id'])) {
    jsonResponse(['error' => 'Autentificare necesară'], 401);
}

// Obține anii din database_years cu numărul de containere
$years = dbFetchAll("
    SELECT
        dy.year,
        dy.is_active,
        COUNT(me.id) as count
    FROM database_years dy
    LEFT JOIN manifest_entries me ON me.database_year_id = dy.id
    GROUP BY dy.id, dy.year, dy.is_active
    ORDER BY dy.year DESC
");

// Dacă nu există ani, adaugă cel curent
if (empty($years)) {
    $currentYear = date('Y');
    $years = [['year' => $currentYear, 'count' => 0, 'is_active' => 1]];
}

jsonResponse(['data' => $years]);
