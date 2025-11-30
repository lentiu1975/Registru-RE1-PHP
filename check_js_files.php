<?php
echo "<h1>Fișiere JavaScript pe Server</h1>";

// Caută toate fișierele .js în assets/js
$jsDir = 'assets/js';
if (is_dir($jsDir)) {
    echo "<h2>Fișiere în $jsDir:</h2><ul>";
    $files = scandir($jsDir);
    foreach ($files as $file) {
        if (pathinfo($file, PATHINFO_EXTENSION) === 'js') {
            $size = filesize($jsDir . '/' . $file);
            echo "<li><strong>$file</strong> - $size bytes</li>";
        }
    }
    echo "</ul>";
} else {
    echo "<p style='color: red;'>Directory $jsDir NU există!</p>";
}

// Caută fișiere .js în root
echo "<h2>Fișiere .js în root:</h2><ul>";
$rootFiles = glob('*.js');
foreach ($rootFiles as $file) {
    $size = filesize($file);
    echo "<li><strong>$file</strong> - $size bytes</li>";
}
echo "</ul>";

// Caută recursiv pentru share-modal.js și modal.js
echo "<h2>Căutare share-modal.js și modal.js:</h2>";
function searchJS($dir, $searchFor) {
    $results = [];
    if (!is_dir($dir)) return $results;

    $items = scandir($dir);
    foreach ($items as $item) {
        if ($item === '.' || $item === '..') continue;
        $path = $dir . '/' . $item;

        if (is_dir($path)) {
            $results = array_merge($results, searchJS($path, $searchFor));
        } elseif (in_array($item, $searchFor)) {
            $results[] = $path;
        }
    }
    return $results;
}

$searchFor = ['share-modal.js', 'modal.js'];
$found = searchJS('.', $searchFor);

if (count($found) > 0) {
    echo "<p style='color: orange;'><strong>GĂSITE:</strong></p><ul>";
    foreach ($found as $path) {
        echo "<li>$path</li>";
    }
    echo "</ul>";
} else {
    echo "<p style='color: green;'><strong>✓ NU s-au găsit fișiere share-modal.js sau modal.js</strong></p>";
}
?>
