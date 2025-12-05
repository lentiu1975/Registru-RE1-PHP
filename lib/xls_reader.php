<?php
/**
 * XLS Reader - Minimalist XLS parser for BIFF8 format
 * Based on OLE compound document structure
 */

class XLSReader {
    private $data;
    private $sheets = [];
    private $sst = []; // Shared string table

    public function __construct($filename) {
        $this->data = file_get_contents($filename);
        if ($this->data === false) {
            throw new Exception("Cannot read file: $filename");
        }
        $this->parse();
    }

    public function getSheetData($sheetIndex = 0) {
        return isset($this->sheets[$sheetIndex]) ? $this->sheets[$sheetIndex] : [];
    }

    private function parse() {
        // Check OLE signature
        $sig = substr($this->data, 0, 8);
        if ($sig !== "\xD0\xCF\x11\xE0\xA1\xB1\x1A\xE1") {
            throw new Exception("Not a valid OLE file");
        }

        // Find Workbook stream in OLE compound document
        $workbookData = $this->extractWorkbookStream();
        if (!$workbookData) {
            throw new Exception("Cannot find Workbook stream");
        }

        $this->parseWorkbook($workbookData);
    }

    private function extractWorkbookStream() {
        // OLE header parsing
        $sectorSize = pow(2, $this->getWord(30));
        $miniSectorSize = pow(2, $this->getWord(32));
        $fatSectors = $this->getDWord(44);
        $directorySectorStart = $this->getDWord(48);
        $miniFatStart = $this->getDWord(60);
        $miniFatSectors = $this->getDWord(64);
        $difatStart = $this->getDWord(68);

        // Read FAT
        $fat = [];
        $difat = [];

        // First 109 DIFAT entries in header
        for ($i = 0; $i < 109; $i++) {
            $difat[] = $this->getDWord(76 + $i * 4);
        }

        // Build FAT from DIFAT
        foreach ($difat as $sector) {
            if ($sector >= 0 && $sector < 0xFFFFFFFC) {
                $offset = 512 + $sector * $sectorSize;
                for ($i = 0; $i < $sectorSize / 4; $i++) {
                    $fat[] = $this->getDWord($offset + $i * 4);
                }
            }
        }

        // Read directory
        $dirData = $this->readStreamFromFat($directorySectorStart, $fat, $sectorSize);

        // Find Workbook entry
        $workbookSector = -1;
        $workbookSize = 0;
        $entrySize = 128;

        for ($i = 0; $i < strlen($dirData) / $entrySize; $i++) {
            $entry = substr($dirData, $i * $entrySize, $entrySize);
            $nameLen = ord($entry[64]) + ord($entry[65]) * 256;
            $name = '';
            for ($j = 0; $j < min($nameLen, 64) - 2; $j += 2) {
                $c = ord($entry[$j]);
                if ($c > 0) $name .= chr($c);
            }

            if (strtolower($name) === 'workbook' || strtolower($name) === 'book') {
                $workbookSector = ord($entry[116]) + ord($entry[117]) * 256 +
                                  ord($entry[118]) * 65536 + ord($entry[119]) * 16777216;
                $workbookSize = ord($entry[120]) + ord($entry[121]) * 256 +
                                ord($entry[122]) * 65536 + ord($entry[123]) * 16777216;
                break;
            }
        }

        if ($workbookSector < 0) {
            return null;
        }

        return $this->readStreamFromFat($workbookSector, $fat, $sectorSize, $workbookSize);
    }

    private function readStreamFromFat($startSector, $fat, $sectorSize, $maxSize = 0) {
        $stream = '';
        $sector = $startSector;
        $count = 0;

        while ($sector >= 0 && $sector < 0xFFFFFFFC) {
            $offset = 512 + $sector * $sectorSize;
            if ($offset + $sectorSize > strlen($this->data)) break;
            $stream .= substr($this->data, $offset, $sectorSize);

            if (isset($fat[$sector])) {
                $sector = $fat[$sector];
            } else {
                break;
            }

            $count++;
            if ($count > 10000) break; // Prevent infinite loop
        }

        if ($maxSize > 0) {
            $stream = substr($stream, 0, $maxSize);
        }

        return $stream;
    }

    private function parseWorkbook($data) {
        $pos = 0;
        $len = strlen($data);
        $currentSheet = [];
        $inSheet = false;

        while ($pos + 4 <= $len) {
            $recType = ord($data[$pos]) + ord($data[$pos + 1]) * 256;
            $recLen = ord($data[$pos + 2]) + ord($data[$pos + 3]) * 256;

            if ($pos + 4 + $recLen > $len) break;

            $recData = substr($data, $pos + 4, $recLen);

            switch ($recType) {
                case 0x00FC: // SST - Shared String Table
                    $this->parseSST($recData);
                    break;

                case 0x0809: // BOF - Beginning of file/sheet
                    if (ord($recData[2]) == 0x10) { // Worksheet
                        $inSheet = true;
                        $currentSheet = [];
                    }
                    break;

                case 0x000A: // EOF
                    if ($inSheet) {
                        $this->sheets[] = $currentSheet;
                        $inSheet = false;
                    }
                    break;

                case 0x0203: // NUMBER
                case 0x027E: // RK
                    if ($inSheet && $recLen >= 6) {
                        $row = ord($recData[0]) + ord($recData[1]) * 256;
                        $col = ord($recData[2]) + ord($recData[3]) * 256;
                        if ($recType == 0x0203 && $recLen >= 14) {
                            $value = $this->readDouble(substr($recData, 6, 8));
                        } else {
                            $value = $this->readRK(substr($recData, 6, 4));
                        }
                        if (!isset($currentSheet[$row])) $currentSheet[$row] = [];
                        $currentSheet[$row][$col] = $value;
                    }
                    break;

                case 0x00FD: // LABELSST - String from SST
                    if ($inSheet && $recLen >= 6) {
                        $row = ord($recData[0]) + ord($recData[1]) * 256;
                        $col = ord($recData[2]) + ord($recData[3]) * 256;
                        $sstIndex = ord($recData[6]) + ord($recData[7]) * 256 +
                                    ord($recData[8]) * 65536 + ord($recData[9]) * 16777216;
                        $value = isset($this->sst[$sstIndex]) ? $this->sst[$sstIndex] : '';
                        if (!isset($currentSheet[$row])) $currentSheet[$row] = [];
                        $currentSheet[$row][$col] = $value;
                    }
                    break;

                case 0x0204: // LABEL (old)
                    if ($inSheet && $recLen >= 8) {
                        $row = ord($recData[0]) + ord($recData[1]) * 256;
                        $col = ord($recData[2]) + ord($recData[3]) * 256;
                        $strLen = ord($recData[6]) + ord($recData[7]) * 256;
                        $value = substr($recData, 8, $strLen);
                        if (!isset($currentSheet[$row])) $currentSheet[$row] = [];
                        $currentSheet[$row][$col] = $value;
                    }
                    break;

                case 0x00BD: // MULRK - Multiple RK values
                    if ($inSheet && $recLen >= 6) {
                        $row = ord($recData[0]) + ord($recData[1]) * 256;
                        $colFirst = ord($recData[2]) + ord($recData[3]) * 256;
                        $numValues = (($recLen - 6) / 6);
                        for ($i = 0; $i < $numValues; $i++) {
                            $col = $colFirst + $i;
                            $rkData = substr($recData, 4 + $i * 6 + 2, 4);
                            $value = $this->readRK($rkData);
                            if (!isset($currentSheet[$row])) $currentSheet[$row] = [];
                            $currentSheet[$row][$col] = $value;
                        }
                    }
                    break;
            }

            $pos += 4 + $recLen;
        }
    }

    private function parseSST($data) {
        if (strlen($data) < 8) return;

        $totalStrings = ord($data[0]) + ord($data[1]) * 256 +
                        ord($data[2]) * 65536 + ord($data[3]) * 16777216;
        $uniqueStrings = ord($data[4]) + ord($data[5]) * 256 +
                         ord($data[6]) * 65536 + ord($data[7]) * 16777216;

        $pos = 8;
        $len = strlen($data);

        for ($i = 0; $i < $uniqueStrings && $pos < $len; $i++) {
            if ($pos + 3 > $len) break;

            $strLen = ord($data[$pos]) + ord($data[$pos + 1]) * 256;
            $flags = ord($data[$pos + 2]);
            $pos += 3;

            $isUnicode = ($flags & 0x01) != 0;
            $hasRichText = ($flags & 0x08) != 0;
            $hasAsian = ($flags & 0x04) != 0;

            $rtRuns = 0;
            $asianSize = 0;

            if ($hasRichText && $pos + 2 <= $len) {
                $rtRuns = ord($data[$pos]) + ord($data[$pos + 1]) * 256;
                $pos += 2;
            }

            if ($hasAsian && $pos + 4 <= $len) {
                $asianSize = ord($data[$pos]) + ord($data[$pos + 1]) * 256 +
                             ord($data[$pos + 2]) * 65536 + ord($data[$pos + 3]) * 16777216;
                $pos += 4;
            }

            $byteLen = $isUnicode ? $strLen * 2 : $strLen;

            if ($pos + $byteLen > $len) {
                $byteLen = $len - $pos;
            }

            $str = substr($data, $pos, $byteLen);
            $pos += $byteLen;

            if ($isUnicode) {
                $str = $this->unicodeToUtf8($str);
            }

            $this->sst[] = $str;

            // Skip rich text and asian data
            $pos += $rtRuns * 4 + $asianSize;
        }
    }

    private function unicodeToUtf8($str) {
        $result = '';
        for ($i = 0; $i < strlen($str) - 1; $i += 2) {
            $code = ord($str[$i]) + ord($str[$i + 1]) * 256;
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

    private function readDouble($data) {
        if (strlen($data) < 8) return 0;
        $arr = unpack('d', $data);
        return $arr[1];
    }

    private function readRK($data) {
        if (strlen($data) < 4) return 0;
        $val = ord($data[0]) + ord($data[1]) * 256 + ord($data[2]) * 65536 + ord($data[3]) * 16777216;

        $isInt = ($val & 0x02) != 0;
        $isDiv100 = ($val & 0x01) != 0;

        if ($isInt) {
            $result = ($val >> 2) & 0x3FFFFFFF;
            if ($val & 0x80000000) {
                $result = -((~$result + 1) & 0x3FFFFFFF);
            }
        } else {
            $hex = str_pad(dechex($val & 0xFFFFFFFC), 8, '0', STR_PAD_LEFT);
            $packed = pack('H*', '00000000' . $hex);
            $arr = unpack('d', $packed);
            $result = $arr[1];
        }

        if ($isDiv100) {
            $result /= 100;
        }

        return $result;
    }

    private function getWord($offset) {
        if ($offset + 2 > strlen($this->data)) return 0;
        return ord($this->data[$offset]) + ord($this->data[$offset + 1]) * 256;
    }

    private function getDWord($offset) {
        if ($offset + 4 > strlen($this->data)) return 0;
        return ord($this->data[$offset]) + ord($this->data[$offset + 1]) * 256 +
               ord($this->data[$offset + 2]) * 65536 + ord($this->data[$offset + 3]) * 16777216;
    }

    /**
     * Convert sheet data to simple 2D array
     */
    public function toArray($sheetIndex = 0) {
        $sheetData = $this->getSheetData($sheetIndex);
        if (empty($sheetData)) return [];

        // Find max row and column
        $maxRow = max(array_keys($sheetData));
        $maxCol = 0;
        foreach ($sheetData as $row) {
            if (!empty($row)) {
                $maxCol = max($maxCol, max(array_keys($row)));
            }
        }

        // Build result array
        $result = [];
        for ($r = 0; $r <= $maxRow; $r++) {
            $rowData = [];
            for ($c = 0; $c <= $maxCol; $c++) {
                $rowData[] = isset($sheetData[$r][$c]) ? $sheetData[$r][$c] : '';
            }
            $result[] = $rowData;
        }

        return $result;
    }
}
