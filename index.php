<?php
session_start();
require_once 'config/database.php';
require_once 'includes/functions.php';

// Verifică autentificare
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

// Verifică dacă utilizatorul există în baza de date și este activ
$currentUser = dbFetchOne("SELECT * FROM users WHERE id = ? AND is_active = 1", [$_SESSION['user_id']]);
if (!$currentUser) {
    session_destroy();
    header('Location: login.php');
    exit;
}

// Încarcă anii disponibili din baza de date
$years = getAllYears();
$activeYear = getActiveYear();
?>
<!DOCTYPE html>
<html lang="ro">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registru Import RE1</title>
    <link rel="stylesheet" href="/assets/css/search-style.css">
</head>
<body>
    <!-- Search Container -->
    <div class="search-container">
        <!-- Header -->
        <header class="search-header">
            <div class="header-content">
                <h1>Registru Import RE1</h1>
                <div class="header-user-actions">
                    <span class="username-display"><?= htmlspecialchars($currentUser['username']) ?></span>
                    <?php if ($currentUser['is_admin']): ?>
                    <a href="/admin_new.php" class="admin-link">Admin</a>
                    <?php endif; ?>
                    <button onclick="window.location.href='/logout.php'" class="logout-button">Ieșire</button>
                </div>
            </div>
        </header>

        <!-- Info Bar -->
        <div class="info-bar">
            <div class="info-bar-content">
                <button onclick="window.location.href='/index.php'" class="home-button">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"></path>
                        <polyline points="9 22 9 12 15 12 15 22"></polyline>
                    </svg>
                    Acasă
                </button>
                <div id="latestManifestInfo" class="latest-manifest-info">
                    <span class="loading-text">Se încarcă...</span>
                </div>
                <div class="home-button-spacer" aria-hidden="true"></div>
            </div>
        </div>

        <!-- Content -->
        <div class="search-content">
            <!-- Search Section -->
            <div class="search-section">
                <form id="searchForm" class="search-form">
                    <div class="form-row-horizontal">
                        <div class="form-group year-select">
                            <label for="year">An:</label>
                            <select id="year" class="year-dropdown">
                                <?php foreach ($years as $year): ?>
                                <option value="<?= $year['id'] ?>" <?= ($year['is_active'] ? 'selected' : '') ?>>
                                    <?= $year['year'] ?><?= ($year['is_active'] ? ' (Activ)' : '') ?>
                                </option>
                                <?php endforeach; ?>
                                <?php if (empty($years)): ?>
                                <option value=""><?= date('Y') ?></option>
                                <?php endif; ?>
                            </select>
                        </div>

                        <div class="form-group container-search">
                            <label for="container">Cautare Container (minim 7 cifre):</label>
                            <input
                                type="text"
                                id="searchInput"
                                placeholder="Ex: ABCD1234567"
                                class="container-input"
                                autocomplete="off">
                        </div>

                        <button type="submit" class="search-button">Cautare</button>
                    </div>
                </form>

                <!-- Loading Indicator -->
                <div id="searchLoading" class="loading-indicator" style="display: none;">
                    <div class="spinner"></div>
                    <p>Se cauta...</p>
                </div>

                <!-- Results Container -->
                <div id="searchResults"></div>
            </div>
        </div>
    </div>

    <script src="/assets/js/search-app.js"></script>
</body>
</html>
