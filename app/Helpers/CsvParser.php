<?php

declare(strict_types=1);

namespace App\Helpers;

/**
 * CsvParser — Structured CSV ingestion with header validation and row-level error reporting.
 *
 * Designed specifically for the Kinara Store Hub bulk-import workflows.
 * The canonical use-case is inventory CSV import; the class is general enough
 * to be extended for other import types (e.g. customers, pricing overrides).
 *
 * Expected inventory CSV column headers (order-independent, case-insensitive):
 *   sku, name, category, uom, selling_price, cost_price, stock_quantity, reorder_point
 *
 * Import semantics (from spec.md):
 *   - Upsert on SKU: update all fields if the SKU exists in the store, insert if new.
 *   - After import, a summary is returned: X inserted, Y updated, Z failed.
 *   - A single import batch is capped at 5 000 rows.
 *
 * This class only handles parsing and validation — the actual DB upsert is
 * performed by InventoryService after the rows are returned.
 */
class CsvParser
{
    /** Maximum number of data rows accepted in a single upload. */
    private const MAX_ROWS = 5000;

    /**
     * Expected column headers for an inventory import CSV (lowercase, trimmed).
     *
     * @var string[]
     */
    public const INVENTORY_HEADERS = [
        'sku',
        'name',
        'category',
        'uom',
        'selling_price',
        'cost_price',
        'stock_quantity',
        'reorder_point',
    ];

    // -----------------------------------------------------------------------
    // Public API
    // -----------------------------------------------------------------------

    /**
     * Parse a CSV file and return validated rows plus a list of row-level errors.
     *
     * Processing steps:
     *  1. Open the file with fopen in read mode.
     *  2. Read the first non-blank line as the header row.
     *  3. Validate that all $expectedHeaders appear in the file header
     *     (case-insensitive, leading/trailing whitespace stripped).
     *  4. Read each subsequent line, skip blank rows, build an associative
     *     array keyed by the normalised header names.
     *  5. Stop and return an error when row count exceeds MAX_ROWS.
     *
     * @param string   $filePath        Absolute path to the uploaded CSV file.
     * @param string[] $expectedHeaders Headers that must be present (case-insensitive).
     *
     * @return array{
     *     rows:   array<int, array<string, string>>,
     *     errors: string[]
     * }
     */
    public static function parse(string $filePath, array $expectedHeaders): array
    {
        $rows   = [];
        $errors = [];

        // --- Open file ---
        if (!file_exists($filePath) || !is_readable($filePath)) {
            return ['rows' => [], 'errors' => ['File not found or not readable: ' . $filePath]];
        }

        $handle = fopen($filePath, 'r');

        if ($handle === false) {
            return ['rows' => [], 'errors' => ['Unable to open file for reading.']];
        }

        try {
            // --- Read header row (skip blank lines at the very top) ---
            $fileHeaders = null;

            while (($line = fgetcsv($handle, 0, ',', '"', '\\')) !== false) {
                // fgetcsv returns [null] for a completely blank line.
                if ($line === [null] || $line === []) {
                    continue;
                }

                // Normalise: trim whitespace and convert to lowercase.
                $fileHeaders = array_map(
                    static fn(string $h): string => mb_strtolower(trim($h)),
                    $line
                );
                break;
            }

            if ($fileHeaders === null) {
                return ['rows' => [], 'errors' => ['CSV file is empty or contains only blank rows.']];
            }

            // --- Validate that all expected headers are present ---
            $normalised = array_map(
                static fn(string $h): string => mb_strtolower(trim($h)),
                $expectedHeaders
            );

            $missing = array_diff($normalised, $fileHeaders);

            if (!empty($missing)) {
                return [
                    'rows'   => [],
                    'errors' => ['Missing required column(s): ' . implode(', ', $missing)],
                ];
            }

            // --- Read data rows ---
            $rowNumber = 1; // 1-indexed; row 1 is the header, so data starts at 2.

            while (($line = fgetcsv($handle, 0, ',', '"', '\\')) !== false) {
                $rowNumber++;

                // Skip blank rows (fgetcsv returns [null] for empty lines).
                if ($line === [null] || $line === []) {
                    continue;
                }

                // Enforce the row limit before processing.
                if (count($rows) >= self::MAX_ROWS) {
                    $errors[] = 'Row limit of ' . self::MAX_ROWS . ' exceeded. Import aborted; only the first ' . self::MAX_ROWS . ' data rows were parsed.';
                    break;
                }

                // Map values to header keys.  Pad $line to match header count
                // in case trailing empty cells were omitted by the exporter.
                $padded = array_pad($line, count($fileHeaders), '');
                $record = [];

                foreach ($fileHeaders as $index => $header) {
                    $record[$header] = trim($padded[$index] ?? '');
                }

                $rows[] = $record;
            }
        } finally {
            fclose($handle);
        }

        return ['rows' => $rows, 'errors' => $errors];
    }

    /**
     * Validate a single inventory CSV row after it has been parsed by parse().
     *
     * Checks performed:
     *  - Required fields (sku, name, selling_price, stock_quantity) must be non-empty.
     *  - Numeric fields (selling_price, cost_price, stock_quantity, reorder_point)
     *    must be valid numbers >= 0 when present.
     *
     * @param array<string, string> $row Associative row from parse()['rows'] (keys lowercase).
     *
     * @return string|null Human-readable error message if the row is invalid, null if valid.
     */
    public static function validateInventoryRow(array $row): ?string
    {
        // --- Required field presence ---
        $required = ['sku', 'name', 'selling_price', 'stock_quantity'];

        foreach ($required as $field) {
            if (!isset($row[$field]) || trim($row[$field]) === '') {
                return "Missing required field: {$field}";
            }
        }

        // --- Numeric field validation ---
        $numericFields = ['selling_price', 'cost_price', 'stock_quantity', 'reorder_point'];

        foreach ($numericFields as $field) {
            // Only validate if the field is present and non-empty (cost_price
            // and reorder_point are optional columns).
            if (!isset($row[$field]) || $row[$field] === '') {
                continue;
            }

            $value = $row[$field];

            // is_numeric() accepts integers and floats including scientific notation.
            if (!is_numeric($value)) {
                return "Field '{$field}' must be a number (got: {$value})";
            }

            if ((float) $value < 0) {
                return "Field '{$field}' must be >= 0 (got: {$value})";
            }
        }

        // --- SKU must not contain characters that break the normalised key ---
        $sku = trim($row['sku']);

        if ($sku === '') {
            return 'SKU cannot be blank after trimming whitespace';
        }

        return null; // Row is valid.
    }
}
