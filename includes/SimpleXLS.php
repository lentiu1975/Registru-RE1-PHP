<?php
/**
 * SimpleXLS - Parser simplu pentru fișiere XLS (Excel 97-2003)
 * Bazat pe specificația BIFF8
 * Cu suport complet pentru SST CONTINUE records
 */

class SimpleXLS {
    private $data;
    private $sst = [];
    private $sheets = [];
    private $error = '';

    public static function parse($filename) {
        $instance = new self();
        if ($instance->read($filename)) {
            return $instance;
        }
        return false;
    }

    public static function parseData($data) {
        $instance = new self();
        $instance->data = $data;
        if ($instance->parseContent()) {
            return $instance;
        }
        return false;
    }

    public static function parseError() {
        return 'Parse error';
    }

    public function rows($sheetIndex = 0) {
        return $this->sheets[$sheetIndex] ?? [];
    }

    public function error() {
        return $this->error;
    }

    private function read($filename) {
        if (!file_exists($filename)) {
            $this->error = 'File not found';
            return false;
        }

        $this->data = file_get_contents($filename);
        if ($this->data === false) {
            $this->error = 'Cannot read file';
            return false;
        }

        return $this->parseContent();
    }

    private function parseContent() {
        if (substr($this->data, 0, 8) !== "\xD0\xCF\x11\xE0\xA1\xB1\x1A\xE1") {
            $this->error = 'Not a valid XLS file';
            return false;
        }

        try {
            $workbook = $this->extractWorkbook();
            if ($workbook === false) {
                return false;
            }
            return $this->parseBIFF($workbook);
        } catch (Exception $e) {
            $this->error = $e->getMessage();
            return false;
        }
    }

    private function extractWorkbook() {
        $sectorSize = pow(2, $this->getWord($this->data, 30));
        $fatSectors = $this->getDWord($this->data, 44);
        $directorySectorStart = $this->getDWord($this->data, 48);

        $fat = [];
        $fatPos = 76;
        for ($i = 0; $i < min(109, $fatSectors); $i++) {
            $fatSector = $this->getDWord($this->data, $fatPos + $i * 4);
            if ($fatSector < 0xFFFFFFFE) {
                $sectorData = substr($this->data, 512 + $fatSector * $sectorSize, $sectorSize);
                for ($j = 0; $j < $sectorSize / 4; $j++) {
                    $fat[] = $this->getDWord($sectorData, $j * 4);
                }
            }
        }

        $dirData = '';
        $sector = $directorySectorStart;
        while ($sector < 0xFFFFFFFE && isset($fat[$sector])) {
            $dirData .= substr($this->data, 512 + $sector * $sectorSize, $sectorSize);
            $sector = $fat[$sector];
            if (strlen($dirData) > 1000000) break;
        }

        $workbookStart = -1;
        $workbookSize = 0;
        for ($i = 0; $i < strlen($dirData) / 128; $i++) {
            $entry = substr($dirData, $i * 128, 128);
            $nameLen = $this->getWord($entry, 64);
            $name = '';
            for ($j = 0; $j < $nameLen - 2; $j += 2) {
                $name .= $entry[$j];
            }
            if (stripos($name, 'Workbook') !== false || stripos($name, 'Book') !== false) {
                $workbookStart = $this->getDWord($entry, 116);
                $workbookSize = $this->getDWord($entry, 120);
                break;
            }
        }

        if ($workbookStart < 0) {
            $this->error = 'Workbook stream not found';
            return false;
        }

        $workbook = '';
        $sector = $workbookStart;
        while ($sector < 0xFFFFFFFE && strlen($workbook) < $workbookSize) {
            if (!isset($fat[$sector])) break;
            $workbook .= substr($this->data, 512 + $sector * $sectorSize, $sectorSize);
            $sector = $fat[$sector];
        }

        return substr($workbook, 0, $workbookSize);
    }

    private function parseBIFF($workbook) {
        $pos = 0;
        $len = strlen($workbook);
        $currentSheet = [];
        $sheetIndex = 0;

        // Colectează SST și CONTINUE records separat
        $sstRecords = [];
        $collectingSST = false;
        $tempPos = 0;

        while ($tempPos < $len - 4) {
            $recordType = $this->getWord($workbook, $tempPos);
            $recordLen = $this->getWord($workbook, $tempPos + 2);
            $recordData = substr($workbook, $tempPos + 4, $recordLen);

            if ($recordType == 0x00FC) { // SST
                $sstRecords = [$recordData];
                $collectingSST = true;
            } elseif ($recordType == 0x003C && $collectingSST) { // CONTINUE
                $sstRecords[] = $recordData;
            } elseif ($collectingSST && $recordType != 0x003C) {
                $collectingSST = false;
            }

            $tempPos += 4 + $recordLen;
        }

        // Parsează SST cu CONTINUE support
        if (!empty($sstRecords)) {
            $this->parseSSTWithContinue($sstRecords);
        }

        // A doua trecere pentru celule
        while ($pos < $len - 4) {
            $recordType = $this->getWord($workbook, $pos);
            $recordLen = $this->getWord($workbook, $pos + 2);
            $recordData = substr($workbook, $pos + 4, $recordLen);

            switch ($recordType) {
                case 0x0203: // NUMBER
                    $row = $this->getWord($recordData, 0);
                    $col = $this->getWord($recordData, 2);
                    $value = $this->getDouble($recordData, 6);
                    if (!isset($currentSheet[$row])) $currentSheet[$row] = [];
                    $currentSheet[$row][$col] = $value;
                    break;

                case 0x00FD: // LABELSST
                    $row = $this->getWord($recordData, 0);
                    $col = $this->getWord($recordData, 2);
                    $sstIndex = $this->getDWord($recordData, 6);
                    if (!isset($currentSheet[$row])) $currentSheet[$row] = [];
                    $currentSheet[$row][$col] = $this->sst[$sstIndex] ?? '';
                    break;

                case 0x0204: // LABEL
                    $row = $this->getWord($recordData, 0);
                    $col = $this->getWord($recordData, 2);
                    $strLen = $this->getWord($recordData, 6);
                    $value = substr($recordData, 8, $strLen);
                    if (!isset($currentSheet[$row])) $currentSheet[$row] = [];
                    $currentSheet[$row][$col] = $value;
                    break;

                case 0x027E: // RK
                    $row = $this->getWord($recordData, 0);
                    $col = $this->getWord($recordData, 2);
                    $value = $this->parseRK(substr($recordData, 6, 4));
                    if (!isset($currentSheet[$row])) $currentSheet[$row] = [];
                    $currentSheet[$row][$col] = $value;
                    break;

                case 0x00BD: // MULRK
                    $row = $this->getWord($recordData, 0);
                    $colFirst = $this->getWord($recordData, 2);
                    $colLast = $this->getWord($recordData, $recordLen - 2);
                    $numCols = $colLast - $colFirst + 1;
                    for ($i = 0; $i < $numCols; $i++) {
                        $rkData = substr($recordData, 4 + $i * 6 + 2, 4);
                        $value = $this->parseRK($rkData);
                        if (!isset($currentSheet[$row])) $currentSheet[$row] = [];
                        $currentSheet[$row][$colFirst + $i] = $value;
                    }
                    break;

                case 0x000A: // EOF
                    if (!empty($currentSheet)) {
                        $this->sheets[$sheetIndex] = $this->normalizeSheet($currentSheet);
                        $sheetIndex++;
                        $currentSheet = [];
                    }
                    break;
            }

            $pos += 4 + $recordLen;
        }

        if (!empty($currentSheet)) {
            $this->sheets[$sheetIndex] = $this->normalizeSheet($currentSheet);
        }

        return !empty($this->sheets);
    }

    /**
     * Parsează SST cu suport complet pentru CONTINUE records
     */
    private function parseSSTWithContinue($records) {
        if (empty($records)) return;

        $firstRecord = $records[0];
        $uniqueStrings = $this->getDWord($firstRecord, 4);

        // Creăm un buffer de bytes din toate recordurile
        // și ținem minte granițele pentru a gestiona Unicode flag
        $buffers = [];
        foreach ($records as $idx => $rec) {
            $buffers[] = [
                'data' => $rec,
                'pos' => 0,
                'len' => strlen($rec)
            ];
        }

        $bufIdx = 0;
        $bufPos = 8; // Skip totalStrings + uniqueStrings în primul record

        // Funcții helper pentru citire cu suport CONTINUE
        $readByte = function() use (&$buffers, &$bufIdx, &$bufPos) {
            while ($bufIdx < count($buffers)) {
                if ($bufPos < $buffers[$bufIdx]['len']) {
                    $byte = ord($buffers[$bufIdx]['data'][$bufPos]);
                    $bufPos++;
                    return $byte;
                }
                $bufIdx++;
                $bufPos = 0;
            }
            return null;
        };

        $readWord = function() use ($readByte) {
            $lo = $readByte();
            $hi = $readByte();
            if ($lo === null || $hi === null) return null;
            return $lo | ($hi << 8);
        };

        $readDWord = function() use ($readByte) {
            $b0 = $readByte();
            $b1 = $readByte();
            $b2 = $readByte();
            $b3 = $readByte();
            if ($b0 === null) return null;
            return $b0 | ($b1 << 8) | ($b2 << 16) | ($b3 << 24);
        };

        // Citește string cu suport pentru continuare la granița de record
        $readString = function($charCount, $isUnicode) use (&$buffers, &$bufIdx, &$bufPos, $readByte) {
            $str = '';
            $charsRead = 0;
            $currentUnicode = $isUnicode;

            while ($charsRead < $charCount) {
                // Verifică dacă trebuie să trecem la următorul buffer
                if ($bufIdx < count($buffers) && $bufPos >= $buffers[$bufIdx]['len']) {
                    // Trecem la CONTINUE record
                    $bufIdx++;
                    if ($bufIdx >= count($buffers)) break;
                    $bufPos = 0;

                    // Primul byte din CONTINUE este flag-ul Unicode pentru continuare
                    $contFlag = ord($buffers[$bufIdx]['data'][0]);
                    $currentUnicode = ($contFlag & 0x01) != 0;
                    $bufPos = 1;
                }

                if ($bufIdx >= count($buffers)) break;

                $bytesPerChar = $currentUnicode ? 2 : 1;
                $charsNeeded = $charCount - $charsRead;
                $bytesAvailable = $buffers[$bufIdx]['len'] - $bufPos;
                $charsCanRead = intval($bytesAvailable / $bytesPerChar);
                $charsToRead = min($charsNeeded, $charsCanRead);

                if ($charsToRead <= 0) {
                    // Nu avem suficienți bytes pentru un caracter, trecem la următorul buffer
                    $bufIdx++;
                    if ($bufIdx >= count($buffers)) break;
                    $bufPos = 0;
                    $contFlag = ord($buffers[$bufIdx]['data'][0]);
                    $currentUnicode = ($contFlag & 0x01) != 0;
                    $bufPos = 1;
                    continue;
                }

                $bytesToRead = $charsToRead * $bytesPerChar;
                $chunk = substr($buffers[$bufIdx]['data'], $bufPos, $bytesToRead);
                $bufPos += $bytesToRead;

                if ($currentUnicode) {
                    // Convert UTF-16LE to UTF-8
                    for ($i = 0; $i + 1 < strlen($chunk); $i += 2) {
                        $code = ord($chunk[$i]) | (ord($chunk[$i + 1]) << 8);
                        if ($code < 0x80) {
                            $str .= chr($code);
                        } elseif ($code < 0x800) {
                            $str .= chr(0xC0 | ($code >> 6));
                            $str .= chr(0x80 | ($code & 0x3F));
                        } else {
                            $str .= chr(0xE0 | ($code >> 12));
                            $str .= chr(0x80 | (($code >> 6) & 0x3F));
                            $str .= chr(0x80 | ($code & 0x3F));
                        }
                    }
                } else {
                    $str .= $chunk;
                }

                $charsRead += $charsToRead;
            }

            return $str;
        };

        // Skip bytes cu suport pentru granițe de buffer
        $skipBytes = function($count) use (&$buffers, &$bufIdx, &$bufPos) {
            $remaining = $count;
            while ($remaining > 0 && $bufIdx < count($buffers)) {
                $available = $buffers[$bufIdx]['len'] - $bufPos;
                if ($available >= $remaining) {
                    $bufPos += $remaining;
                    $remaining = 0;
                } else {
                    $remaining -= $available;
                    $bufIdx++;
                    $bufPos = 0;
                }
            }
        };

        // Parsează fiecare string
        for ($strNum = 0; $strNum < $uniqueStrings; $strNum++) {
            $charCount = $readWord();
            if ($charCount === null) break;

            $flags = $readByte();
            if ($flags === null) break;

            $isUnicode = ($flags & 0x01) != 0;
            $hasRichText = ($flags & 0x08) != 0;
            $hasAsian = ($flags & 0x04) != 0;

            $richTextRuns = 0;
            $asianSize = 0;

            if ($hasRichText) {
                $richTextRuns = $readWord();
                if ($richTextRuns === null) $richTextRuns = 0;
            }

            if ($hasAsian) {
                $asianSize = $readDWord();
                if ($asianSize === null) $asianSize = 0;
            }

            // Citește stringul
            $str = $readString($charCount, $isUnicode);
            $this->sst[] = $str;

            // Skip rich text formatting runs
            if ($richTextRuns > 0) {
                $skipBytes($richTextRuns * 4);
            }

            // Skip asian phonetic data
            if ($asianSize > 0) {
                $skipBytes($asianSize);
            }
        }
    }

    private function parseRK($data) {
        $rk = $this->getDWord($data, 0);
        $isInt = ($rk & 0x02) != 0;
        $div100 = ($rk & 0x01) != 0;

        if ($isInt) {
            $value = $rk >> 2;
            if ($rk & 0x80000000) {
                $value = -((~$rk >> 2) + 1);
            }
        } else {
            $hex = sprintf('%08X', $rk & 0xFFFFFFFC) . '00000000';
            $value = $this->hexToDouble($hex);
        }

        if ($div100) {
            $value /= 100;
        }

        return $value;
    }

    private function normalizeSheet($sheet) {
        if (empty($sheet)) return [];

        $maxRow = max(array_keys($sheet));
        $maxCol = 0;
        foreach ($sheet as $row) {
            if (!empty($row)) {
                $maxCol = max($maxCol, max(array_keys($row)));
            }
        }

        $normalized = [];
        for ($r = 0; $r <= $maxRow; $r++) {
            $normalized[$r] = [];
            for ($c = 0; $c <= $maxCol; $c++) {
                $normalized[$r][$c] = $sheet[$r][$c] ?? '';
            }
        }

        return $normalized;
    }

    private function getWord($data, $pos) {
        if ($pos + 2 > strlen($data)) return 0;
        return ord($data[$pos]) | (ord($data[$pos + 1]) << 8);
    }

    private function getDWord($data, $pos) {
        if ($pos + 4 > strlen($data)) return 0;
        return ord($data[$pos]) | (ord($data[$pos + 1]) << 8) |
               (ord($data[$pos + 2]) << 16) | (ord($data[$pos + 3]) << 24);
    }

    private function getDouble($data, $pos) {
        if ($pos + 8 > strlen($data)) return 0;
        $hex = '';
        for ($i = 7; $i >= 0; $i--) {
            $hex .= sprintf('%02X', ord($data[$pos + $i]));
        }
        return $this->hexToDouble($hex);
    }

    private function hexToDouble($hex) {
        $bin = '';
        for ($i = 0; $i < 16; $i++) {
            $bin .= str_pad(base_convert($hex[$i], 16, 2), 4, '0', STR_PAD_LEFT);
        }

        $sign = $bin[0] == '1' ? -1 : 1;
        $exp = bindec(substr($bin, 1, 11)) - 1023;
        $mantissa = 1;
        for ($i = 0; $i < 52; $i++) {
            if ($bin[12 + $i] == '1') {
                $mantissa += pow(2, -($i + 1));
            }
        }

        if ($exp == -1023) return 0;
        return $sign * $mantissa * pow(2, $exp);
    }

    private function utf16ToUtf8($str) {
        $result = '';
        for ($i = 0; $i < strlen($str); $i += 2) {
            if ($i + 1 >= strlen($str)) break;
            $code = ord($str[$i]) | (ord($str[$i + 1]) << 8);
            if ($code < 0x80) {
                $result .= chr($code);
            } elseif ($code < 0x800) {
                $result .= chr(0xC0 | ($code >> 6));
                $result .= chr(0x80 | ($code & 0x3F));
            } else {
                $result .= chr(0xE0 | ($code >> 12));
                $result .= chr(0x80 | (($code >> 6) & 0x3F));
                $result .= chr(0x80 | ($code & 0x3F));
            }
        }
        return $result;
    }
}
