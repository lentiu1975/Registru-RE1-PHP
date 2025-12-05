<?php
/**
 * SimpleXLS - Parser simplu pentru fișiere XLS (Excel 97-2003)
 * Bazat pe specificația BIFF8
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
        // Verifică semnătura OLE2
        if (substr($this->data, 0, 8) !== "\xD0\xCF\x11\xE0\xA1\xB1\x1A\xE1") {
            $this->error = 'Not a valid XLS file';
            return false;
        }

        try {
            // Parsează structura OLE2 pentru a găsi Workbook stream
            $workbook = $this->extractWorkbook();
            if ($workbook === false) {
                return false;
            }

            // Parsează BIFF records
            return $this->parseBIFF($workbook);
        } catch (Exception $e) {
            $this->error = $e->getMessage();
            return false;
        }
    }

    private function extractWorkbook() {
        // Citește header-ul OLE2
        $sectorSize = pow(2, $this->getWord($this->data, 30));
        $miniSectorSize = pow(2, $this->getWord($this->data, 32));
        $fatSectors = $this->getDWord($this->data, 44);
        $directorySectorStart = $this->getDWord($this->data, 48);
        $miniFatStart = $this->getDWord($this->data, 60);
        $miniFatSectors = $this->getDWord($this->data, 64);
        $difatStart = $this->getDWord($this->data, 68);

        // Citește FAT
        $fat = [];
        $fatPos = 76; // DIFAT starts at offset 76
        for ($i = 0; $i < min(109, $fatSectors); $i++) {
            $fatSector = $this->getDWord($this->data, $fatPos + $i * 4);
            if ($fatSector < 0xFFFFFFFE) {
                $sectorData = substr($this->data, 512 + $fatSector * $sectorSize, $sectorSize);
                for ($j = 0; $j < $sectorSize / 4; $j++) {
                    $fat[] = $this->getDWord($sectorData, $j * 4);
                }
            }
        }

        // Citește Directory
        $dirData = '';
        $sector = $directorySectorStart;
        while ($sector < 0xFFFFFFFE && isset($fat[$sector])) {
            $dirData .= substr($this->data, 512 + $sector * $sectorSize, $sectorSize);
            $sector = $fat[$sector];
            if (strlen($dirData) > 1000000) break; // Safety limit
        }

        // Găsește Workbook entry
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

        // Extrage Workbook stream
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

        while ($pos < $len - 4) {
            $recordType = $this->getWord($workbook, $pos);
            $recordLen = $this->getWord($workbook, $pos + 2);
            $recordData = substr($workbook, $pos + 4, $recordLen);

            switch ($recordType) {
                case 0x00FC: // SST - Shared String Table
                    $this->parseSST($recordData);
                    break;

                case 0x0203: // NUMBER
                    $row = $this->getWord($recordData, 0);
                    $col = $this->getWord($recordData, 2);
                    $value = $this->getDouble($recordData, 6);
                    if (!isset($currentSheet[$row])) $currentSheet[$row] = [];
                    $currentSheet[$row][$col] = $value;
                    break;

                case 0x00FD: // LABELSST - String from SST
                    $row = $this->getWord($recordData, 0);
                    $col = $this->getWord($recordData, 2);
                    $sstIndex = $this->getDWord($recordData, 6);
                    if (!isset($currentSheet[$row])) $currentSheet[$row] = [];
                    $currentSheet[$row][$col] = $this->sst[$sstIndex] ?? '';
                    break;

                case 0x0204: // LABEL (old format)
                    $row = $this->getWord($recordData, 0);
                    $col = $this->getWord($recordData, 2);
                    $strLen = $this->getWord($recordData, 6);
                    $value = substr($recordData, 8, $strLen);
                    if (!isset($currentSheet[$row])) $currentSheet[$row] = [];
                    $currentSheet[$row][$col] = $value;
                    break;

                case 0x027E: // RK - Compressed number
                    $row = $this->getWord($recordData, 0);
                    $col = $this->getWord($recordData, 2);
                    $value = $this->parseRK(substr($recordData, 6, 4));
                    if (!isset($currentSheet[$row])) $currentSheet[$row] = [];
                    $currentSheet[$row][$col] = $value;
                    break;

                case 0x00BD: // MULRK - Multiple RK
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

                case 0x000A: // EOF - End of sheet
                    if (!empty($currentSheet)) {
                        $this->sheets[$sheetIndex] = $this->normalizeSheet($currentSheet);
                        $sheetIndex++;
                        $currentSheet = [];
                    }
                    break;
            }

            $pos += 4 + $recordLen;
        }

        // Adaugă ultimul sheet dacă există date
        if (!empty($currentSheet)) {
            $this->sheets[$sheetIndex] = $this->normalizeSheet($currentSheet);
        }

        return !empty($this->sheets);
    }

    private function parseSST($data) {
        $totalStrings = $this->getDWord($data, 0);
        $uniqueStrings = $this->getDWord($data, 4);
        $pos = 8;
        $len = strlen($data);

        for ($i = 0; $i < $uniqueStrings && $pos < $len; $i++) {
            if ($pos + 3 > $len) break;

            $charCount = $this->getWord($data, $pos);
            $flags = ord($data[$pos + 2]);
            $pos += 3;

            $isUnicode = ($flags & 0x01) != 0;
            $hasRichText = ($flags & 0x08) != 0;
            $hasAsian = ($flags & 0x04) != 0;

            if ($hasRichText && $pos + 2 <= $len) {
                $richTextRuns = $this->getWord($data, $pos);
                $pos += 2;
            }
            if ($hasAsian && $pos + 4 <= $len) {
                $asianSize = $this->getDWord($data, $pos);
                $pos += 4;
            }

            $strLen = $isUnicode ? $charCount * 2 : $charCount;
            if ($pos + $strLen > $len) {
                $strLen = $len - $pos;
            }

            $str = substr($data, $pos, $strLen);
            if ($isUnicode) {
                $str = $this->utf16ToUtf8($str);
            }
            $this->sst[] = $str;
            $pos += $strLen;

            if ($hasRichText && isset($richTextRuns)) {
                $pos += $richTextRuns * 4;
            }
            if ($hasAsian && isset($asianSize)) {
                $pos += $asianSize;
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
