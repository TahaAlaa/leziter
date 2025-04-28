<?php

namespace App\Leziter\PdfParsing;

use DateTime;
use Exception;

class WarrantyPdfParser extends PdfParser
{
    private string $pdfContent;

    /**
     * @throws Exception
     */
    public function parse(string $file): array
    {
        $this->pdfContent = $this->getText($file);
        return $this->readWarrantyItems();
    }

    private function readWarrantyItems(): array
    {
        $pdfLines = explode("\n", $this->pdfContent);
        $start = false;
        $warrantyItems = [];
        $groupValidFrom = null;

        foreach ($pdfLines as $line) {
            // Trim the line to avoid leading/trailing whitespace issues
            $trimmedLine = trim($line);

            if (str_starts_with($trimmedLine, 'Jótállás kezdete:')) {
                preg_match('/(\d{4}\.\d{2}\.\d{2}\.\s\d{1,2}:\d{2}:\d{2})/', $trimmedLine, $matches);
                $date = $matches[0] ?? '';
                $date = DateTime::createFromFormat('Y.m.d. H:i:s', $date);
                if ($date === false) {
                    preg_match('/(\d{4}\.\d{2}\.\d{2}\.)/', $trimmedLine, $matches);
                    $date = $matches[0] ?? '';
                    $date = DateTime::createFromFormat('Y.m.d.', $date);
                }
                if ($date === false) {
                    continue;
                }
                $groupValidFrom = $date->format('Y-m-d');
                continue;
            }

            // Check for the start line
            if (str_starts_with($trimmedLine, "Cikkszám")) {
                $start = true;
                continue; // Skip the line with "Cikkszám"
            }

            // Check for the end line
            if (str_starts_with($trimmedLine, "Összesen:") || str_starts_with($trimmedLine, 'Készült az Europroof')) {
                $start = false;
                continue; // Stop processing after "Összesen:"
            }

            // Capture lines between the start and end
            if ($start && !empty($trimmedLine)) {
                $item = $this->readWarrantyColumns($trimmedLine);
                if (!empty($item)) {
                    $item['valid_from'] = $groupValidFrom;
                    $warrantyItems[] = $item;
                } else {
                    dump('Invalid item: ' . $trimmedLine);
                }
            }
        }

        return $warrantyItems;
    }

    /**
     * @param string $line
     * @return array
     */
    private function readWarrantyColumns(string $line): array
    {
        $columns = preg_split('/\s{2,}/', $line);
        // Map the columns to respective fields
        if (count($columns) < 4) return []; // Lines like "SET" and no more columns

        $sku = $columns[0];
        // If the SKU is too long but not multi-line SKU but only 1 space between the columns edge case
        if (strlen($columns[0]) > 18) {
            list($columns[0], $columns[1]) = explode(" ", $columns[0], 2);
            dump($columns);
        }

        $validMonthsColumn = $columns[3];
        if (!str_contains($validMonthsColumn, 'hónap')) {
            foreach ($columns as $column) {
                if (str_contains($column, 'hónap')) {
                    $validMonthsColumn = $column;
                    break;
                }
            }
        }

        preg_match('/(?P<months>\d+)\s*\w*/', $validMonthsColumn, $matches);
        $validMonths = $matches['months'] ?? null;

        /**
         * More columns if needed
         * 'name' => $columns[1] ?? '',
         * 'qty' => $columns[2] ?? '',
         * 'sale_price'  => $columns[4] ?? ''
         */
        return [
            'valid_from' => null,
            'sku' => $sku ?? '',
            'valid_months' => (int)$validMonths,
            'document_id' => null,
        ];
    }
}
