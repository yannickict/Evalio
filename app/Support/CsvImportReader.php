<?php

namespace App\Support;

use Generator;
use Illuminate\Http\UploadedFile;
use Illuminate\Validation\ValidationException;

class CsvImportReader
{
    /**
     * @param  list<string>  $requiredHeaders
     * @return Generator<int, array{row: int, data: array<string, string>, error: string|null}>
     */
    public function rows(UploadedFile $file, array $requiredHeaders): Generator
    {
        $handle = fopen($file->getPathname(), 'r');
        if ($handle === false) {
            throw ValidationException::withMessages(['file' => __('The CSV file could not be read.')]);
        }

        try {
            $header = fgetcsv($handle, null, ',', '"', '');
            if ($header === false) {
                throw ValidationException::withMessages(['file' => __('The CSV file is empty.')]);
            }

            $header = array_map(fn ($value): string => trim((string) $value), $header);
            if (str_starts_with($header[0], "\xEF\xBB\xBF")) {
                $header[0] = substr($header[0], 3);
            }
            if (in_array('', $header, true)
                || count($header) !== count(array_unique($header))
                || array_diff($requiredHeaders, $header)) {
                throw ValidationException::withMessages([
                    'file' => __('Use unique, non-empty headers including: :headers.', ['headers' => implode(', ', $requiredHeaders)]),
                ]);
            }

            $rowNumber = 1;
            while (($row = fgetcsv($handle, null, ',', '"', '')) !== false) {
                $rowNumber++;
                if ($row === [null]) {
                    continue;
                }

                if (count($row) !== count($header)) {
                    yield ['row' => $rowNumber, 'data' => [], 'error' => __('The number of values does not match the headers.')];

                    continue;
                }

                yield [
                    'row' => $rowNumber,
                    'data' => array_combine($header, array_map(fn ($value): string => trim((string) $value), $row)),
                    'error' => null,
                ];
            }
        } finally {
            fclose($handle);
        }
    }
}
