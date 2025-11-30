<?php
session_start();
$_SESSION['user_id'] = 1; // Simulează autentificarea

error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h1>Test API Details</h1>";

// Testează cu manifest 153
$manifestNumber = '153';

echo "<h2>Testing manifest: $manifestNumber</h2>";

require_once 'config/database.php';
require_once 'includes/functions.php';

$conn = getDbConnection();

echo "<h3>1. Test manifest info query:</h3>";
try {
    $manifestInfo = dbFetchOne(
        "SELECT
            m.manifest_number,
            m.arrival_date,
            MAX(m.created_at) as created_at,
            MAX(me.nume_nava) as ship_name
         FROM manifests m
         LEFT JOIN manifest_entries me ON m.manifest_number = me.permit_number
         WHERE m.manifest_number = ?
         GROUP BY m.manifest_number",
        [$manifestNumber]
    );

    echo "<pre>";
    print_r($manifestInfo);
    echo "</pre>";
} catch (Exception $e) {
    echo "<p style='color: red;'>ERROR: " . $e->getMessage() . "</p>";
}

echo "<h3>2. Test containers query:</h3>";
try {
    $containers = dbFetchAll(
        "SELECT
            id,
            numar_curent,
            numar_manifest,
            numar_permis,
            numar_pozitie,
            cerere_operatiune,
            data_inregistrare,
            container as container_number,
            model_container,
            tip_container,
            numar_colete,
            greutate_bruta,
            descriere_marfa as goods_description,
            seal_number,
            tip_operatiune,
            nume_nava as ship_name,
            pavilion_nava,
            numar_sumara,
            linie_maritima,
            observatii,
            created_at
        FROM manifest_entries
        WHERE permit_number = ?
        ORDER BY numar_curent ASC
        LIMIT 3",
        [$manifestNumber]
    );

    echo "<p>Found " . count($containers) . " containers</p>";
    echo "<pre>";
    print_r($containers);
    echo "</pre>";
} catch (Exception $e) {
    echo "<p style='color: red;'>ERROR: " . $e->getMessage() . "</p>";
    echo "<pre>" . $e->getTraceAsString() . "</pre>";
}

echo "<hr><h3>3. Test API call:</h3>";
echo "<p><a href='api/manifests/details.php?manifest_number=" . urlencode($manifestNumber) . "' target='_blank'>Open API in new tab</a></p>";
?>
