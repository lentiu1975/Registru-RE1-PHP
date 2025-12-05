<?php
/**
 * API pentru Import Excel
 * Suportă: Import Standard și Import cu Template
 */

// Previne orice output înainte de JSON
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);

session_start();
require_once '../config/database.php';
require_once '../includes/functions.php';

header('Content-Type: application/json; charset=utf-8');

// Global error handler pentru a returna JSON în loc de erori PHP
set_error_handler(function($severity, $message, $file, $line) {
    throw new ErrorException($message, 0, $severity, $file, $line);
});

set_exception_handler(function($e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Eroare server: ' . $e->getMessage(),
        'debug' => $e->getFile() . ':' . $e->getLine()
    ]);
    exit;
});

// Verifică autentificare
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Neautentificat']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Metodă nepermisă']);
    exit;
}

// Verifică fișier
if (!isset($_FILES['excel_file']) || $_FILES['excel_file']['error'] !== UPLOAD_ERR_OK) {
    $errorMessages = [
        UPLOAD_ERR_INI_SIZE => 'Fișierul depășește limita de upload',
        UPLOAD_ERR_FORM_SIZE => 'Fișierul depășește limita formularului',
        UPLOAD_ERR_PARTIAL => 'Fișierul a fost uploadat parțial',
        UPLOAD_ERR_NO_FILE => 'Niciun fișier uploadat',
        UPLOAD_ERR_NO_TMP_DIR => 'Folder temporar lipsă',
        UPLOAD_ERR_CANT_WRITE => 'Nu se poate scrie pe disc',
    ];
    $errorCode = $_FILES['excel_file']['error'] ?? UPLOAD_ERR_NO_FILE;
    $errorMsg = $errorMessages[$errorCode] ?? 'Eroare necunoscută la upload';
    echo json_encode(['success' => false, 'error' => $errorMsg]);
    exit;
}

$file = $_FILES['excel_file'];
$importType = $_POST['import_type'] ?? 'standard';
$templateId = $_POST['template_id'] ?? null;
$startRow = intval($_POST['start_row'] ?? 2);

// Câmpuri de override (pentru import cu template)
$overrideManifest = trim($_POST['override_manifest'] ?? '');
$overridePermis = trim($_POST['override_permis'] ?? '');
$overrideCerere = trim($_POST['override_cerere'] ?? '');
$overrideNava = trim($_POST['override_nava'] ?? '');
$overridePavilion = trim($_POST['override_pavilion'] ?? '');
$overrideLinie = trim($_POST['override_linie'] ?? '');
$overrideData = trim($_POST['override_data'] ?? '');
$allowUpdate = isset($_POST['allow_update']) && $_POST['allow_update'] == '1';

// Verifică extensie fișier
$fileName = $file['name'];
$fileExt = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));

if (!in_array($fileExt, ['xls', 'xlsx'])) {
    echo json_encode(['success' => false, 'error' => 'Doar fișiere XLS sau XLSX sunt permise']);
    exit;
}

// Configurare mapare coloane
$columnMapping = [];

if ($importType === 'template' && $templateId) {
    // Încarcă template-ul
    $template = dbFetchOne("SELECT * FROM import_templates WHERE id = ?", [$templateId]);
    if (!$template) {
        echo json_encode(['success' => false, 'error' => 'Template-ul selectat nu există']);
        exit;
    }
    // Coloana în DB este column_mappings (cu 's')
    $mappingsJson = $template['column_mappings'] ?? $template['column_mapping'] ?? '{}';
    $columnMapping = json_decode($mappingsJson, true) ?: [];
    $startRow = intval($template['start_row'] ?? 2);
} else {
    // Mapare standard - coloanele din Django
    $columnMapping = [
        'numar_manifest' => 0,      // A
        'container' => 1,            // B
        'tip_container' => 2,        // C
        'numar_colete' => 3,         // D
        'greutate_bruta' => 4,       // E
        'descriere_marfa' => 5,      // F
        'tip_operatiune' => 6,       // G
        'nume_nava' => 7,            // H
        'pavilion_nava' => 8,        // I
        'numar_sumara' => 9,         // J
        'linie_maritima' => 10,      // K
        'numar_permis' => 11,        // L
        'numar_pozitie' => 12,       // M
        'cerere_operatiune' => 13,   // N
        'data_inregistrare' => 14,   // O
        'observatii' => 15           // P
    ];
}

try {
    // Verifică dacă există PhpSpreadsheet
    $phpSpreadsheetPath = __DIR__ . '/../vendor/autoload.php';
    $usePhpSpreadsheet = file_exists($phpSpreadsheetPath);

    if ($usePhpSpreadsheet) {
        require_once $phpSpreadsheetPath;
        $spreadsheet = \PhpOffice\PhpSpreadsheet\IOFactory::load($file['tmp_name']);
        $worksheet = $spreadsheet->getActiveSheet();
        $rows = $worksheet->toArray(null, true, true, false);
    } else {
        // Fallback: folosește parsere simple
        if ($fileExt === 'xlsx') {
            $rows = parseXLSX($file['tmp_name']);
        } else {
            // Pentru XLS folosim SimpleXLS
            require_once __DIR__ . '/../includes/SimpleXLS.php';
            $xls = SimpleXLS::parse($file['tmp_name']);
            if ($xls) {
                $rows = $xls->rows();
            } else {
                echo json_encode(['success' => false, 'error' => 'Nu s-a putut citi fișierul XLS. Verifică formatul fișierului.']);
                exit;
            }
        }
    }

    if (empty($rows)) {
        echo json_encode(['success' => false, 'error' => 'Fișierul este gol sau nu poate fi citit']);
        exit;
    }

    $conn = getDbConnection();
    $conn->begin_transaction();

    $imported = 0;
    $updated = 0;
    $skipped = 0;
    $errors = 0;
    $errorDetails = [];
    $debugLog = [];
    $total = 0;

    // Obține anul activ pentru import
    $activeYearId = getActiveYearId();
    if (!$activeYearId) {
        echo json_encode(['success' => false, 'error' => 'Nu există an activ configurat. Configurați un an în Admin > Ani Baze Date.']);
        exit;
    }

    // Cache pentru manifest IDs - evită crearea de duplicate
    $manifestCache = [];

    // Procesăm rândurile începând de la startRow (1-based)
    for ($i = $startRow - 1; $i < count($rows); $i++) {
        $row = $rows[$i];
        $total++;
        $rowNum = $i + 1;

        // Skip linii goale
        if (empty(array_filter($row, function($v) { return $v !== null && $v !== ''; }))) {
            continue;
        }

        // Extrage valorile conform mapării
        $data = [];
        foreach ($columnMapping as $field => $colIndex) {
            if (is_numeric($colIndex)) {
                $data[$field] = isset($row[$colIndex]) ? trim(strval($row[$colIndex])) : '';
            } else {
                // Dacă e litera coloanei (A, B, C, AA, AB, AJ, etc.)
                $letters = strtoupper(trim($colIndex));
                $colNum = 0;
                for ($j = 0; $j < strlen($letters); $j++) {
                    $colNum = $colNum * 26 + (ord($letters[$j]) - ord('A') + 1);
                }
                $colNum--; // Convert to 0-based index
                $data[$field] = isset($row[$colNum]) ? trim(strval($row[$colNum])) : '';
            }
        }

        // Determină valorile efective pentru manifest (cu override)
        $effectiveManifest = !empty($overrideManifest) ? $overrideManifest : ($data['numar_manifest'] ?? '');

        // Curăță containerul - elimină spațiile (ex: "UACU  4770120" -> "UACU4770120")
        $containerVal = preg_replace('/\s+/', '', $data['container'] ?? '');
        $coleteVal = floatval($data['numar_colete'] ?? 0);
        $greutateVal = floatval(str_replace(',', '.', $data['greutate_bruta'] ?? 0));

        // DEBUG: Log toate rândurile sărite
        $debugLog[] = "Row $rowNum: container='$containerVal' (len=" . strlen($containerVal) . "), colete=$coleteVal, greutate=$greutateVal";

        // Skip dacă containerul e gol sau prea scurt (minim 4 caractere)
        if (empty($containerVal) || strlen($containerVal) < 4) {
            $skipped++;
            $debugLog[] = "  -> SKIP: container invalid (empty or len<4)";
            continue;
        }

        // Skip dacă colete=0 SAU greutate=0 (ambele trebuie să aibă valori > 0)
        if ($coleteVal <= 0 || $greutateVal <= 0) {
            $skipped++;
            $debugLog[] = "  -> SKIP: colete=$coleteVal, greutate=$greutateVal (one is 0)";
            continue;
        }

        $debugLog[] = "  -> OK: will import";

        // Verifică duplicate DOAR dacă allowUpdate e activ (pentru a actualiza intrări existente)
        // Containerul poate apărea de mai multe ori în același manifest, deci NU sărim duplicatele
        $existingEntry = null;
        if ($allowUpdate && !empty($data['container']) && !empty($effectiveManifest)) {
            // Caută doar pentru UPDATE, nu pentru skip
            $existingEntry = dbFetchOne(
                "SELECT id, packages, weight FROM manifest_entries WHERE container_number = ? AND numar_manifest = ?",
                [$data['container'], $effectiveManifest]
            );
        }

        // Convertește tip operațiune: IMP -> I, TSH -> T
        $tipOperatiune = strtoupper(trim($data['tip_operatiune'] ?? ''));
        if ($tipOperatiune === 'IMP' || $tipOperatiune === 'IMPORT') {
            $tipOperatiune = 'I';
        } elseif ($tipOperatiune === 'TSH' || $tipOperatiune === 'TRANZIT' || $tipOperatiune === 'TRANSHIPMENT') {
            $tipOperatiune = 'T';
        } elseif (strlen($tipOperatiune) > 1) {
            // Dacă e altceva mai lung, ia doar prima literă
            $tipOperatiune = substr($tipOperatiune, 0, 1);
        }

        // Pregătește datele pentru insert
        $insertData = [
            'numar_manifest' => $data['numar_manifest'] ?? '',
            'container_number' => $containerVal,  // Folosește valoarea curățată (fără spații)
            'container_type' => $data['tip_container'] ?? '',
            'packages' => intval($data['numar_colete'] ?? 0),
            'weight' => floatval(str_replace(',', '.', $data['greutate_bruta'] ?? 0)),
            'goods_description' => $data['descriere_marfa'] ?? '',
            'operation_type' => $tipOperatiune,
            'ship_name' => $data['nume_nava'] ?? '',
            'ship_flag' => $data['pavilion_nava'] ?? '',
            'summary_number' => $data['numar_sumara'] ?? '',
            'linie_maritima' => $data['linie_maritima'] ?? '',
            'permit_number' => $data['numar_permis'] ?? '',
            'position_number' => $data['numar_pozitie'] ?? '',
            'operation_request' => $data['cerere_operatiune'] ?? '',
            'observatii' => $data['observatii'] ?? ''
        ];

        // Aplică override-uri (dacă sunt setate)
        if (!empty($overrideManifest)) {
            $insertData['numar_manifest'] = $overrideManifest;
        }
        if (!empty($overridePermis)) {
            $insertData['permit_number'] = $overridePermis;
        }
        if (!empty($overrideCerere)) {
            $insertData['operation_request'] = $overrideCerere;
        }
        if (!empty($overrideNava)) {
            $insertData['ship_name'] = $overrideNava;
        }
        if (!empty($overridePavilion)) {
            $insertData['ship_flag'] = $overridePavilion;
        }
        if (!empty($overrideLinie)) {
            $insertData['linie_maritima'] = $overrideLinie;
        }

        // Parsează data
        $dataInreg = null;
        // Prioritate: override > din fișier
        if (!empty($overrideData)) {
            $dataInreg = parseDate($overrideData);
        } elseif (!empty($data['data_inregistrare'])) {
            $dataInreg = parseDate($data['data_inregistrare']);
        }

        // Generează model_container (prefix 4 caractere + tip container)
        // Ex: MRSU1234567 + 45G1 = MRSU45G1
        $modelContainer = '';
        if (!empty($insertData['container_number']) && !empty($insertData['container_type'])) {
            $prefix = substr($insertData['container_number'], 0, 4);
            $modelContainer = $prefix . $insertData['container_type'];

            // Auto-creează tipul în container_types dacă nu există
            $tipContainer = $insertData['container_type'];
            $checkType = $conn->prepare("SELECT id FROM container_types WHERE model_container = ?");
            $checkType->bind_param("s", $modelContainer);
            $checkType->execute();
            $typeResult = $checkType->get_result();

            if ($typeResult->num_rows == 0) {
                // Determină descrierea bazată pe dimensiune
                $sizePrefix = substr($tipContainer, 0, 2);
                if ($sizePrefix == '22') {
                    $size = '20ft';
                } elseif ($sizePrefix == '42' || $sizePrefix == '45' || $sizePrefix == 'L5') {
                    $size = '40ft';
                } else {
                    $size = '';
                }
                $descriere = "Container $modelContainer - $size";

                $insertType = $conn->prepare("INSERT INTO container_types (model_container, tip_container, descriere) VALUES (?, ?, ?)");
                $insertType->bind_param("sss", $modelContainer, $tipContainer, $descriere);
                $insertType->execute();
                $insertType->close();
            }
            $checkType->close();
        }

        // Găsește sau creează manifestul în tabela manifests (pentru foreign key)
        $manifestId = null;
        $manifestNumber = $insertData['numar_manifest'];
        if (!empty($manifestNumber)) {
            // Verifică mai întâi în cache
            if (isset($manifestCache[$manifestNumber])) {
                $manifestId = $manifestCache[$manifestNumber];
            } else {
                // Caută manifest existent în DB
                $manifestRow = dbFetchOne(
                    "SELECT id FROM manifests WHERE manifest_number = ?",
                    [$manifestNumber]
                );

                if ($manifestRow) {
                    $manifestId = $manifestRow['id'];
                } else {
                    // Creează manifest nou cu ship_id și arrival_date
                    // Găsește sau creează nava
                    $shipId = null;
                    $shipName = $insertData['ship_name'];
                    if (!empty($shipName)) {
                        $shipRow = dbFetchOne("SELECT id FROM ships WHERE name = ?", [$shipName]);
                        if ($shipRow) {
                            $shipId = $shipRow['id'];
                        } else {
                            $stmtShip = $conn->prepare("INSERT INTO ships (name) VALUES (?)");
                            $stmtShip->bind_param("s", $shipName);
                            $stmtShip->execute();
                            $shipId = $conn->insert_id;
                            $stmtShip->close();
                        }
                    }

                    // Găsește sau creează pavilionul
                    $shipFlag = $insertData['ship_flag'];
                    if (!empty($shipFlag)) {
                        $flagCode = strtoupper(trim($shipFlag));
                        $flagRow = dbFetchOne("SELECT id FROM pavilions WHERE name = ?", [$flagCode]);
                        if (!$flagRow) {
                            // Creează pavilion nou (name=cod, country_name=cod inițial, poate fi editat ulterior)
                            $stmtFlag = $conn->prepare("INSERT INTO pavilions (name, country_name) VALUES (?, ?)");
                            $stmtFlag->bind_param("ss", $flagCode, $flagCode);
                            $stmtFlag->execute();
                            $stmtFlag->close();
                        }
                    }

                    // Creează manifestul cu toate datele (inclusiv database_year_id)
                    $createdBy = $_SESSION['user_id'] ?? null;
                    $stmtManifest = $conn->prepare(
                        "INSERT INTO manifests (manifest_number, ship_id, arrival_date, created_by, database_year_id, created_at) VALUES (?, ?, ?, ?, ?, NOW())"
                    );
                    $stmtManifest->bind_param("sisii", $manifestNumber, $shipId, $dataInreg, $createdBy, $activeYearId);
                    $stmtManifest->execute();
                    $manifestId = $conn->insert_id;
                    $stmtManifest->close();
                }

                // Salvează în cache
                $manifestCache[$manifestNumber] = $manifestId;
            }
        }

        // Verifică dacă trebuie să facem UPDATE pe înregistrare existentă
        // Condiția: există duplicat și allowUpdate e activ
        $shouldUpdate = ($existingEntry && $allowUpdate);

        try {
            if ($shouldUpdate) {
                // UPDATE - actualizează toate câmpurile (inclusiv packages și weight)
                $sql = "UPDATE manifest_entries SET
                    numar_manifest = ?, container_type = ?, packages = ?, weight = ?,
                    goods_description = ?, operation_type = ?, ship_name = ?, ship_flag = ?,
                    summary_number = ?, linie_maritima = ?, permit_number = ?, position_number = ?,
                    operation_request = ?, data_inregistrare = ?, observatii = ?, model_container = ?,
                    updated_at = NOW()
                    WHERE id = ?";

                $stmt = $conn->prepare($sql);
                $existingId = $existingEntry['id'];
                // 17 params: s-s-i-d-s-s-s-s-s-s-s-s-s-s-s-s-i
                $stmt->bind_param(
                    "ssidssssssssssssi",
                    $insertData['numar_manifest'],
                    $insertData['container_type'],
                    $insertData['packages'],
                    $insertData['weight'],
                    $insertData['goods_description'],
                    $insertData['operation_type'],
                    $insertData['ship_name'],
                    $insertData['ship_flag'],
                    $insertData['summary_number'],
                    $insertData['linie_maritima'],
                    $insertData['permit_number'],
                    $insertData['position_number'],
                    $insertData['operation_request'],
                    $dataInreg,
                    $insertData['observatii'],
                    $modelContainer,
                    $existingId
                );

                if ($stmt->execute()) {
                    $updated++;
                } else {
                    $errors++;
                    $errorDetails[] = "Rând {$rowNum} (update): " . $stmt->error;
                }
                $stmt->close();

            } else if (!$existingEntry) {
                // INSERT - înregistrare nouă (cu manifest_id și database_year_id)
                $sql = "INSERT INTO manifest_entries (
                    manifest_id, database_year_id, numar_manifest, container_number, container_type, packages, weight,
                    goods_description, operation_type, ship_name, ship_flag, summary_number,
                    linie_maritima, permit_number, position_number, operation_request,
                    data_inregistrare, observatii, model_container, created_at
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())";

                $stmt = $conn->prepare($sql);
                // 19 params: i-i-s-s-s-i-d-s-s-s-s-s-s-s-s-s-s-s-s
                $stmt->bind_param(
                    "iisssidssssssssssss",
                    $manifestId,
                    $activeYearId,
                    $insertData['numar_manifest'],
                    $insertData['container_number'],
                    $insertData['container_type'],
                    $insertData['packages'],
                    $insertData['weight'],
                    $insertData['goods_description'],
                    $insertData['operation_type'],
                    $insertData['ship_name'],
                    $insertData['ship_flag'],
                    $insertData['summary_number'],
                    $insertData['linie_maritima'],
                    $insertData['permit_number'],
                    $insertData['position_number'],
                    $insertData['operation_request'],
                    $dataInreg,
                    $insertData['observatii'],
                    $modelContainer
                );

                if ($stmt->execute()) {
                    $imported++;
                } else {
                    $errors++;
                    $errorDetails[] = "Rând {$rowNum}: " . $stmt->error;
                }
                $stmt->close();
            }

        } catch (Exception $e) {
            $errors++;
            $errorDetails[] = "Rând {$rowNum}: " . $e->getMessage();
        }
    }

    $conn->commit();

    // Salvează în import_logs
    $logStatus = ($errors > 0) ? 'partial' : 'success';
    $errorMsg = !empty($errorDetails) ? implode('; ', array_slice($errorDetails, 0, 5)) : null;
    dbQuery(
        "INSERT INTO import_logs (filename, rows_imported, rows_updated, rows_skipped, rows_failed, status, error_message, user_id, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW())",
        [$fileName, $imported, $updated, $skipped, $errors, $logStatus, $errorMsg, $_SESSION['user_id'] ?? null]
    );

    echo json_encode([
        'success' => true,
        'imported' => $imported,
        'updated' => $updated,
        'skipped' => $skipped,
        'errors' => $errors,
        'total' => $total,
        'error_details' => array_slice($errorDetails, 0, 10),
        'debug_log' => $debugLog // DEBUG: toate rândurile procesate
    ]);

} catch (Exception $e) {
    if (isset($conn)) {
        $conn->rollback();
    }

    // Salvează eroarea în import_logs
    dbQuery(
        "INSERT INTO import_logs (filename, rows_imported, rows_failed, status, error_message, user_id, created_at) VALUES (?, 0, 0, 'error', ?, ?, NOW())",
        [$fileName ?? 'unknown', $e->getMessage(), $_SESSION['user_id'] ?? null]
    );

    echo json_encode([
        'success' => false,
        'error' => 'Eroare la procesare: ' . $e->getMessage()
    ]);
}

/**
 * Parser pentru XLSX cu suport pentru poziții celule și rich text
 */
function parseXLSX($filePath) {
    $rows = [];
    $zip = new ZipArchive();
    if ($zip->open($filePath) !== true) return $rows;

    // Citește shared strings
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
                $cellRef = (string)$cell['r'];
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

/**
 * Converteste referinta celulei (A1, B2, AA5) in index coloana (0, 1, 26)
 */
function cellRefToColIndex($cellRef) {
    $letters = preg_replace('/[0-9]+/', '', $cellRef);
    $colIdx = 0;
    for ($i = 0; $i < strlen($letters); $i++) {
        $colIdx = $colIdx * 26 + (ord(strtoupper($letters[$i])) - ord('A') + 1);
    }
    return $colIdx - 1; // 0-based
}

/**
 * Parsează diferite formate de dată
 */
function parseDate($dateStr) {
    if (empty($dateStr)) return null;

    // Încearcă diferite formate
    $formats = [
        'Y-m-d',
        'd.m.Y',
        'd/m/Y',
        'd-m-Y',
        'm/d/Y',
        'Y/m/d'
    ];

    foreach ($formats as $format) {
        $date = DateTime::createFromFormat($format, $dateStr);
        if ($date !== false) {
            return $date->format('Y-m-d');
        }
    }

    // Dacă e număr Excel (serial date)
    if (is_numeric($dateStr)) {
        $unixDate = ($dateStr - 25569) * 86400;
        return date('Y-m-d', $unixDate);
    }

    return null;
}
