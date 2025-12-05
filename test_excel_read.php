<?php
/**
 * Test - citește un Excel și arată primele rânduri
 */
error_reporting(E_ALL);
ini_set('display_errors', 1);

header('Content-Type: text/html; charset=utf-8');

echo "<h2>Test Excel Read</h2>";

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['test_file'])) {
    $file = $_FILES['test_file'];
    $tmpPath = $file['tmp_name'];
    $fileName = $file['name'];
    $fileExt = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));

    echo "<h3>Fișier: " . htmlspecialchars($fileName) . "</h3>";
    echo "<p>Extensie: {$fileExt}</p>";

    $rows = null;

    try {
        if ($fileExt === 'xlsx') {
            // XLSX - folosește parseXLSX
            $rows = parseXLSX($tmpPath);
            echo "<p style='color:green'>✓ XLSX citit cu parseXLSX</p>";
        } else {
            // XLS - folosește XLSReader propriu
            require_once __DIR__ . '/lib/xls_reader.php';
            $excel = new XLSReader($tmpPath);
            $rows = $excel->toArray(0);
            echo "<p style='color:green'>✓ XLS citit cu XLSReader</p>";
        }
    } catch (Exception $e) {
        echo "<p style='color:red'>Excepție: " . $e->getMessage() . "</p>";
    }

    if (!$rows || empty($rows)) {
        echo "<p style='color:red'>Nu s-au putut citi datele!</p>";
        exit;
    }

    echo "<p>Total rânduri citite: <b>" . count($rows) . "</b></p>";

    // Template mapping (Maersk)
    $columnMapping = [
        'container' => 'B',
        'numar_colete' => 'G',
        'numar_sumara' => 'AJ',
        'numar_pozitie' => 'A',
        'tip_container' => 'E',
        'greutate_bruta' => 'H',
        'linie_maritima' => 'W',
        'tip_operatiune' => 'AC',
        'descriere_marfa' => 'U'
    ];

    // Afișează primele 5 rânduri raw
    echo "<h3>Primele 5 rânduri RAW:</h3>";
    echo "<div style='overflow-x:auto'>";
    echo "<table border='1' cellpadding='3' style='font-size:11px'><tr><th>#</th>";

    $maxCols = 0;
    for ($i = 0; $i < min(5, count($rows)); $i++) {
        if (count($rows[$i]) > $maxCols) $maxCols = count($rows[$i]);
    }
    $maxCols = min($maxCols, 40);

    for ($c = 0; $c < $maxCols; $c++) {
        $letter = $c < 26 ? chr(65 + $c) : 'A' . chr(65 + $c - 26);
        echo "<th>{$letter}</th>";
    }
    echo "</tr>";

    for ($i = 0; $i < min(5, count($rows)); $i++) {
        echo "<tr><td><b>{$i}</b></td>";
        for ($c = 0; $c < $maxCols; $c++) {
            $val = isset($rows[$i][$c]) ? htmlspecialchars(substr(strval($rows[$i][$c]), 0, 25)) : '';
            $bg = '';
            if ($c == 1) $bg = 'background:#ffcccc';
            if ($c == 4) $bg = 'background:#ccffcc';
            if ($c == 6) $bg = 'background:#ccccff';
            if ($c == 7) $bg = 'background:#ffccff';
            echo "<td style='{$bg}'>{$val}</td>";
        }
        echo "</tr>";
    }
    echo "</table></div>";

    // Date conform mapping
    echo "<h3>Date extrase conform template Maersk:</h3>";
    echo "<table border='1' cellpadding='5'><tr>";
    foreach ($columnMapping as $field => $col) {
        $letters = strtoupper(trim($col));
        $colNum = 0;
        for ($j = 0; $j < strlen($letters); $j++) {
            $colNum = $colNum * 26 + (ord($letters[$j]) - ord('A') + 1);
        }
        $colNum--;
        echo "<th>{$field}<br><small>col {$col} = idx {$colNum}</small></th>";
    }
    echo "</tr>";

    for ($i = 1; $i < min(6, count($rows)); $i++) {
        $row = $rows[$i];
        echo "<tr>";
        foreach ($columnMapping as $field => $col) {
            $letters = strtoupper(trim($col));
            $colNum = 0;
            for ($j = 0; $j < strlen($letters); $j++) {
                $colNum = $colNum * 26 + (ord($letters[$j]) - ord('A') + 1);
            }
            $colNum--;
            $val = isset($row[$colNum]) ? htmlspecialchars(strval($row[$colNum])) : '<span style="color:red">(empty)</span>';
            echo "<td>{$val}</td>";
        }
        echo "</tr>";
    }
    echo "</table>";

} else {
    echo "<form method='post' enctype='multipart/form-data'>";
    echo "<p>Selectează fișierul Excel pentru test:</p>";
    echo "<input type='file' name='test_file' accept='.xls,.xlsx'><br><br>";
    echo "<button type='submit'>Testează citirea</button>";
    echo "</form>";
}

function parseXLSX($filePath) {
    $rows = [];
    $zip = new ZipArchive();
    if ($zip->open($filePath) !== true) return $rows;

    // Citeste shared strings
    $sharedStrings = [];
    $ssXml = $zip->getFromName('xl/sharedStrings.xml');
    if ($ssXml) {
        $ssData = simplexml_load_string($ssXml);
        foreach ($ssData->si as $si) {
            // Handle both simple <t> and rich text <r><t>
            if (isset($si->t)) {
                $sharedStrings[] = (string)$si->t;
            } elseif (isset($si->r)) {
                $text = '';
                foreach ($si->r as $r) {
                    $text .= (string)$r->t;
                }
                $sharedStrings[] = $text;
            } else {
                $sharedStrings[] = '';
            }
        }
    }

    $sheetXml = $zip->getFromName('xl/worksheets/sheet1.xml');
    if ($sheetXml) {
        $sheetData = simplexml_load_string($sheetXml);
        $maxCol = 0;

        // Prima trecere: gaseste numarul maxim de coloane
        foreach ($sheetData->sheetData->row as $row) {
            foreach ($row->c as $cell) {
                $cellRef = (string)$cell['r']; // ex: "A1", "B1", "AA1"
                $colIdx = cellRefToColIndex($cellRef);
                if ($colIdx > $maxCol) $maxCol = $colIdx;
            }
        }

        // A doua trecere: extrage datele
        foreach ($sheetData->sheetData->row as $row) {
            $rowIdx = (int)$row['r'] - 1; // 0-based

            // Initializeaza randul cu valori goale
            $rowData = array_fill(0, $maxCol + 1, '');

            foreach ($row->c as $cell) {
                $cellRef = (string)$cell['r'];
                $colIdx = cellRefToColIndex($cellRef);

                $value = '';
                if (isset($cell->v)) {
                    $value = (string)$cell->v;
                    // String din shared strings
                    if (isset($cell['t']) && (string)$cell['t'] === 's') {
                        $value = $sharedStrings[(int)$value] ?? '';
                    }
                } elseif (isset($cell->is->t)) {
                    // Inline string
                    $value = (string)$cell->is->t;
                }

                $rowData[$colIdx] = $value;
            }

            // Pune randul la indexul corect
            $rows[$rowIdx] = $rowData;
        }

        // Sorteaza dupa index si re-indexeaza
        ksort($rows);
        $rows = array_values($rows);
    }

    $zip->close();
    return $rows;
}

// Converteste referinta celulei (A1, B2, AA5) in index coloana (0, 1, 26)
function cellRefToColIndex($cellRef) {
    $letters = preg_replace('/[0-9]+/', '', $cellRef);
    $colIdx = 0;
    for ($i = 0; $i < strlen($letters); $i++) {
        $colIdx = $colIdx * 26 + (ord(strtoupper($letters[$i])) - ord('A') + 1);
    }
    return $colIdx - 1; // 0-based
}
