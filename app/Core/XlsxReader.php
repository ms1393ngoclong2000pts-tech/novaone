<?php

declare(strict_types=1);

final class XlsxReader
{
    public static function rows(string $path): array
    {
        $archive = file_get_contents($path);
        if ($archive === false) {
            throw new RuntimeException('Không thể đọc file Excel.');
        }

        $sharedStrings = self::sharedStrings(self::entry($archive, 'xl/sharedStrings.xml'));
        $sheetXml = self::entry($archive, 'xl/worksheets/sheet1.xml');
        if ($sheetXml === null) {
            throw new RuntimeException('Không tìm thấy sheet đầu tiên trong file Excel.');
        }

        $sheet = simplexml_load_string($sheetXml);
        if ($sheet === false) {
            throw new RuntimeException('Dữ liệu sheet Excel không hợp lệ.');
        }

        $sheet->registerXPathNamespace('x', 'http://schemas.openxmlformats.org/spreadsheetml/2006/main');
        $rows = [];
        foreach ($sheet->xpath('//x:sheetData/x:row') ?: [] as $row) {
            $row->registerXPathNamespace('x', 'http://schemas.openxmlformats.org/spreadsheetml/2006/main');
            $values = [];
            foreach ($row->xpath('./x:c') ?: [] as $cell) {
                $cell->registerXPathNamespace('x', 'http://schemas.openxmlformats.org/spreadsheetml/2006/main');
                $reference = (string) ($cell['r'] ?? 'A1');
                preg_match('/^[A-Z]+/', $reference, $match);
                $index = self::columnIndex($match[0] ?? 'A');
                $type = (string) ($cell['t'] ?? '');
                $valueNodes = $cell->xpath('./x:v') ?: [];
                $value = isset($valueNodes[0]) ? (string) $valueNodes[0] : '';

                if ($type === 's') {
                    $value = $sharedStrings[(int) $value] ?? '';
                } elseif ($type === 'inlineStr') {
                    $textNodes = $cell->xpath('.//x:t') ?: [];
                    $value = implode('', array_map('strval', $textNodes));
                }

                $values[$index] = $value;
            }

            if ($values !== []) {
                $last = max(array_keys($values));
                $rows[] = array_map(fn ($index) => $values[$index] ?? '', range(0, $last));
            }
        }

        return $rows;
    }

    private static function sharedStrings(?string $xml): array
    {
        if ($xml === null) {
            return [];
        }

        $document = simplexml_load_string($xml);
        if ($document === false) {
            return [];
        }

        $document->registerXPathNamespace('x', 'http://schemas.openxmlformats.org/spreadsheetml/2006/main');
        $strings = [];
        foreach ($document->xpath('//x:si') ?: [] as $item) {
            $item->registerXPathNamespace('x', 'http://schemas.openxmlformats.org/spreadsheetml/2006/main');
            $parts = $item->xpath('.//x:t') ?: [];
            $strings[] = implode('', array_map('strval', $parts));
        }
        return $strings;
    }

    private static function columnIndex(string $letters): int
    {
        $index = 0;
        foreach (str_split($letters) as $letter) {
            $index = ($index * 26) + ord($letter) - 64;
        }
        return max(0, $index - 1);
    }

    private static function entry(string $archive, string $target): ?string
    {
        $eocd = strrpos($archive, "PK\x05\x06");
        if ($eocd === false || strlen($archive) < $eocd + 22) {
            throw new RuntimeException('File Excel không phải định dạng XLSX hợp lệ.');
        }

        $end = unpack('ventriesDisk/ventries/Vsize/Voffset/vcomment', substr($archive, $eocd + 8, 14));
        $offset = (int) ($end['offset'] ?? 0);
        $entries = (int) ($end['entries'] ?? 0);

        for ($index = 0; $index < $entries; $index++) {
            if (substr($archive, $offset, 4) !== "PK\x01\x02") {
                break;
            }

            $header = unpack(
                'vversionMade/vversionNeeded/vflags/vmethod/vtime/vdate/Vcrc/Vcompressed/Vuncompressed/vnameLength/vextraLength/vcommentLength/vdisk/vinternal/Vexternal/VlocalOffset',
                substr($archive, $offset + 4, 42)
            );
            $name = str_replace('\\', '/', substr($archive, $offset + 46, (int) $header['nameLength']));

            if ($name === $target) {
                $localOffset = (int) $header['localOffset'];
                $local = unpack('vnameLength/vextraLength', substr($archive, $localOffset + 26, 4));
                $dataOffset = $localOffset + 30 + (int) $local['nameLength'] + (int) $local['extraLength'];
                $compressed = substr($archive, $dataOffset, (int) $header['compressed']);

                return match ((int) $header['method']) {
                    0 => $compressed,
                    8 => (($inflated = gzinflate($compressed)) !== false ? $inflated : throw new RuntimeException('Không thể giải nén dữ liệu Excel.')),
                    default => throw new RuntimeException('Phương thức nén Excel chưa được hỗ trợ.'),
                };
            }

            $offset += 46 + (int) $header['nameLength'] + (int) $header['extraLength'] + (int) $header['commentLength'];
        }

        return null;
    }
}
