<?php

namespace App\Services;

use PhpOffice\PhpSpreadsheet\Reader\IReadFilter;

final class SubscriberImportReadFilter implements IReadFilter
{
    private const IMPORT_COLUMNS = ['A', 'B'];

    public function __construct(
        private readonly int $startRow,
        private readonly int $endRow,
    ) {}

    /**
     * Read only the email and name columns for the current chunk.
     */
    public function readCell(string $columnAddress, int $row, string $worksheetName = ''): bool
    {
        return in_array($columnAddress, self::IMPORT_COLUMNS, true)
            && $row >= $this->startRow
            && $row <= $this->endRow;
    }
}
