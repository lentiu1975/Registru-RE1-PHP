<?php
/**
 * Export date pe an ca XLSX real (Excel 2007+)
 * Cu colorare: galben pentru duplicate, roșu pentru observații
 * Folosește format Office Open XML (XLSX) - suportă multe rânduri
 */
session_start();
require_once '../config/database.php';
require_once '../includes/functions.php';

// Verifică autentificare
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    die('Neautentificat');
}

$year = intval($_GET['year'] ?? date('Y'));

// Găsește database_year_id pentru anul selectat
$dbYear = dbFetchOne("SELECT id FROM database_years WHERE year = ?", [$year]);
if (!$dbYear) {
    http_response_code(404);
    die('Anul ' . $year . ' nu există în baza de date');
}
$dbYearId = $dbYear['id'];

// Încarcă datele pentru anul selectat (folosește database_year_id)
$containers = dbFetchAll(
    "SELECT
        id,
        numar_manifest,
        permit_number,
        position_number,
        operation_request,
        data_inregistrare,
        container_number,
        goods_description,
        packages,
        weight,
        operation_type,
        ship_name,
        ship_flag,
        summary_number,
        observatii
    FROM manifest_entries
    WHERE database_year_id = ?
    ORDER BY permit_number ASC, numar_curent ASC, id ASC",
    [$dbYearId]
);

if (empty($containers)) {
    http_response_code(404);
    die('Nu există date pentru anul ' . $year);
}

// Calculează duplicatele (container_number care apare de mai multe ori în același an)
$containerCounts = [];
foreach ($containers as $c) {
    $cn = $c['container_number'];
    if (!isset($containerCounts[$cn])) {
        $containerCounts[$cn] = 0;
    }
    $containerCounts[$cn]++;
}

// Filename
$filename = "Export_{$year}_" . date('Y-m-d_His') . ".xlsx";

// Headers pentru download XLSX
header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
header('Content-Disposition: attachment; filename="' . $filename . '"');
header('Cache-Control: max-age=0');

// Creează XLSX
createXLSX($containers, $containerCounts);
exit;

/**
 * Creează un fișier XLSX folosind ZipArchive și XML
 */
function createXLSX($data, $containerCounts) {
    // Creează fișier temporar
    $tempFile = tempnam(sys_get_temp_dir(), 'xlsx');

    $zip = new ZipArchive();
    if ($zip->open($tempFile, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
        die('Nu pot crea fișierul XLSX');
    }

    // [Content_Types].xml
    $zip->addFromString('[Content_Types].xml', getContentTypes());

    // _rels/.rels
    $zip->addFromString('_rels/.rels', getRels());

    // xl/_rels/workbook.xml.rels
    $zip->addFromString('xl/_rels/workbook.xml.rels', getWorkbookRels());

    // xl/workbook.xml
    $zip->addFromString('xl/workbook.xml', getWorkbook());

    // xl/styles.xml - cu stilurile pentru culori
    $zip->addFromString('xl/styles.xml', getStyles());

    // xl/sharedStrings.xml și xl/worksheets/sheet1.xml
    list($sharedStrings, $sheetData) = generateSheetData($data, $containerCounts);
    $zip->addFromString('xl/sharedStrings.xml', $sharedStrings);
    $zip->addFromString('xl/worksheets/sheet1.xml', $sheetData);

    $zip->close();

    // Output
    readfile($tempFile);
    unlink($tempFile);
}

function getContentTypes() {
    return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types">
    <Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/>
    <Default Extension="xml" ContentType="application/xml"/>
    <Override PartName="/xl/workbook.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet.main+xml"/>
    <Override PartName="/xl/worksheets/sheet1.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml"/>
    <Override PartName="/xl/styles.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.styles+xml"/>
    <Override PartName="/xl/sharedStrings.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sharedStrings+xml"/>
</Types>';
}

function getRels() {
    return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">
    <Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="xl/workbook.xml"/>
</Relationships>';
}

function getWorkbookRels() {
    return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">
    <Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet" Target="worksheets/sheet1.xml"/>
    <Relationship Id="rId2" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/styles" Target="styles.xml"/>
    <Relationship Id="rId3" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/sharedStrings" Target="sharedStrings.xml"/>
</Relationships>';
}

function getWorkbook() {
    return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<workbook xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships">
    <sheets>
        <sheet name="Date Export" sheetId="1" r:id="rId1"/>
    </sheets>
</workbook>';
}

function getStyles() {
    return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<styleSheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">
    <fonts count="2">
        <font>
            <sz val="11"/>
            <name val="Calibri"/>
        </font>
        <font>
            <b/>
            <sz val="11"/>
            <color rgb="FFFFFFFF"/>
            <name val="Calibri"/>
        </font>
    </fonts>
    <fills count="6">
        <fill><patternFill patternType="none"/></fill>
        <fill><patternFill patternType="gray125"/></fill>
        <fill>
            <patternFill patternType="solid">
                <fgColor rgb="FF4472C4"/>
                <bgColor indexed="64"/>
            </patternFill>
        </fill>
        <fill>
            <patternFill patternType="solid">
                <fgColor rgb="FFFFFF00"/>
                <bgColor indexed="64"/>
            </patternFill>
        </fill>
        <fill>
            <patternFill patternType="solid">
                <fgColor rgb="FFFF6B6B"/>
                <bgColor indexed="64"/>
            </patternFill>
        </fill>
        <fill>
            <patternFill patternType="solid">
                <fgColor rgb="FFDCE6F1"/>
                <bgColor indexed="64"/>
            </patternFill>
        </fill>
    </fills>
    <borders count="2">
        <border>
            <left/><right/><top/><bottom/><diagonal/>
        </border>
        <border>
            <left style="thin"><color indexed="64"/></left>
            <right style="thin"><color indexed="64"/></right>
            <top style="thin"><color indexed="64"/></top>
            <bottom style="thin"><color indexed="64"/></bottom>
            <diagonal/>
        </border>
    </borders>
    <cellStyleXfs count="1">
        <xf numFmtId="0" fontId="0" fillId="0" borderId="0"/>
    </cellStyleXfs>
    <cellXfs count="9">
        <xf numFmtId="0" fontId="0" fillId="0" borderId="0" xfId="0"/>
        <xf numFmtId="0" fontId="1" fillId="2" borderId="1" xfId="0" applyFont="1" applyFill="1" applyBorder="1" applyAlignment="1">
            <alignment horizontal="center" vertical="center" wrapText="1"/>
        </xf>
        <xf numFmtId="0" fontId="0" fillId="0" borderId="1" xfId="0" applyBorder="1"/>
        <xf numFmtId="0" fontId="0" fillId="3" borderId="1" xfId="0" applyFill="1" applyBorder="1"/>
        <xf numFmtId="0" fontId="0" fillId="4" borderId="1" xfId="0" applyFill="1" applyBorder="1"/>
        <xf numFmtId="0" fontId="0" fillId="5" borderId="1" xfId="0" applyFill="1" applyBorder="1"/>
        <xf numFmtId="0" fontId="0" fillId="3" borderId="1" xfId="0" applyFill="1" applyBorder="1"/>
        <xf numFmtId="0" fontId="0" fillId="4" borderId="1" xfId="0" applyFill="1" applyBorder="1"/>
        <xf numFmtId="0" fontId="0" fillId="5" borderId="1" xfId="0" applyFill="1" applyBorder="1"/>
    </cellXfs>
</styleSheet>';
}

function generateSheetData($data, $containerCounts) {
    $strings = [];
    $stringIndex = [];

    // Helper pentru a adăuga string în shared strings
    $addString = function($str) use (&$strings, &$stringIndex) {
        $str = (string)$str;
        if (!isset($stringIndex[$str])) {
            $stringIndex[$str] = count($strings);
            $strings[] = $str;
        }
        return $stringIndex[$str];
    };

    // Headers
    $headers = [
        'Numar Manifest', 'Numar Permis', 'Numar Pozitie', 'Cerere Operatiune',
        'Data Inregistrare', 'Container', 'Descriere Marfa', 'Numar Colete',
        'Greutate Bruta', 'Tip Operatiune', 'Nume Nava', 'Pavilion Nava',
        'Numar Sumara', 'Observatii'
    ];

    foreach ($headers as $h) {
        $addString($h);
    }

    // Data
    foreach ($data as $row) {
        $addString($row['numar_manifest'] ?? '');
        $addString($row['permit_number'] ?? '');
        $addString($row['position_number'] ?? '');
        $addString($row['operation_request'] ?? '');
        $addString($row['data_inregistrare'] ? date('d.m.Y', strtotime($row['data_inregistrare'])) : '');
        $addString($row['container_number'] ?? '');
        $addString($row['goods_description'] ?? '');
        $addString($row['packages'] ?? '');
        $addString($row['weight'] ?? '');
        $addString($row['operation_type'] ?? '');
        $addString($row['ship_name'] ?? '');
        $addString($row['ship_flag'] ?? '');
        $addString($row['summary_number'] ?? '');
        $addString($row['observatii'] ?? '');
    }

    // Generează sharedStrings.xml
    $ssXml = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>' . "\n";
    $ssXml .= '<sst xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" count="' . count($strings) . '" uniqueCount="' . count($strings) . '">';
    foreach ($strings as $s) {
        $ssXml .= '<si><t>' . htmlspecialchars($s, ENT_XML1 | ENT_QUOTES, 'UTF-8') . '</t></si>';
    }
    $ssXml .= '</sst>';

    // Generează sheet1.xml
    $sheetXml = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>' . "\n";
    $sheetXml .= '<worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">';

    // Column widths
    $sheetXml .= '<cols>';
    $colWidths = [15, 15, 12, 15, 12, 15, 40, 10, 12, 12, 20, 15, 15, 40];
    for ($i = 1; $i <= 14; $i++) {
        $w = $colWidths[$i-1];
        $sheetXml .= '<col min="' . $i . '" max="' . $i . '" width="' . $w . '" customWidth="1"/>';
    }
    $sheetXml .= '</cols>';

    $sheetXml .= '<sheetData>';

    // Header row (style 1 = header cu fundal albastru)
    $sheetXml .= '<row r="1" spans="1:14">';
    foreach ($headers as $colIndex => $header) {
        $col = chr(65 + $colIndex); // A, B, C, ...
        $strIndex = $stringIndex[$header];
        $sheetXml .= '<c r="' . $col . '1" s="1" t="s"><v>' . $strIndex . '</v></c>';
    }
    $sheetXml .= '</row>';

    // Data rows
    // Stiluri:
    // 2 = alb cu border (manifest impar)
    // 5 = albastru deschis cu border (manifest par)
    // 3 = galben (duplicate) - pe alb
    // 4 = roșu (observații) - pe alb
    // 6 = galben pe albastru
    // 7 = roșu pe albastru

    $rowNum = 2;
    $lastManifest = null;
    $manifestIndex = 0; // pentru alternare culori

    foreach ($data as $row) {
        $containerNum = $row['container_number'] ?? '';
        $observatii = $row['observatii'] ?? '';
        $currentManifest = $row['numar_manifest'] ?? '';

        // Verifică dacă s-a schimbat manifestul
        if ($currentManifest !== $lastManifest) {
            $manifestIndex++;
            $lastManifest = $currentManifest;
        }

        // Determină dacă rândul e pe fundal alb sau albastru
        $isBlueBackground = ($manifestIndex % 2 == 0);

        // Stilul de bază pentru rând
        $baseStyle = $isBlueBackground ? 5 : 2; // 5 = albastru, 2 = alb

        // Determină stilul pentru celula container (coloana F)
        $hasObservations = strlen(trim($observatii)) >= 5;
        $isDuplicate = isset($containerCounts[$containerNum]) && $containerCounts[$containerNum] > 1;

        $containerStyle = $baseStyle; // normal (alb sau albastru)
        if ($hasObservations) {
            $containerStyle = 4; // roșu (prioritate maximă)
        } elseif ($isDuplicate) {
            $containerStyle = 3; // galben
        }

        $sheetXml .= '<row r="' . $rowNum . '" spans="1:14">';

        $values = [
            $row['numar_manifest'] ?? '',
            $row['permit_number'] ?? '',
            $row['position_number'] ?? '',
            $row['operation_request'] ?? '',
            $row['data_inregistrare'] ? date('d.m.Y', strtotime($row['data_inregistrare'])) : '',
            $containerNum,
            $row['goods_description'] ?? '',
            $row['packages'] ?? '',
            $row['weight'] ?? '',
            $row['operation_type'] ?? '',
            $row['ship_name'] ?? '',
            $row['ship_flag'] ?? '',
            $row['summary_number'] ?? '',
            $observatii
        ];

        // Coloane numerice: 0=manifest, 1=permis, 2=pozitie, 3=cerere, 7=colete, 8=greutate
        $numericColumns = [0, 1, 2, 3, 7, 8];

        foreach ($values as $colIndex => $value) {
            $col = chr(65 + $colIndex);

            // Coloana F (index 5) = container, aplică stilul colorat pentru duplicate/observații
            // Restul coloanelor primesc stilul de bază (alb sau albastru)
            $style = ($colIndex == 5) ? $containerStyle : $baseStyle;

            // Pentru coloane numerice, scrie valoarea direct (fără shared string)
            if (in_array($colIndex, $numericColumns) && is_numeric($value) && $value !== '') {
                $sheetXml .= '<c r="' . $col . $rowNum . '" s="' . $style . '"><v>' . $value . '</v></c>';
            } else {
                $strIndex = $stringIndex[(string)$value];
                $sheetXml .= '<c r="' . $col . $rowNum . '" s="' . $style . '" t="s"><v>' . $strIndex . '</v></c>';
            }
        }

        $sheetXml .= '</row>';
        $rowNum++;
    }

    $sheetXml .= '</sheetData>';
    $sheetXml .= '</worksheet>';

    return [$ssXml, $sheetXml];
}
