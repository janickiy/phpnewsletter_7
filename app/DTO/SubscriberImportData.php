<?php

namespace App\DTO;

final class SubscriberImportData
{
    /** @var list<int> */
    public readonly array $categoryIds;

    public readonly string $extension;

    public readonly ?string $charset;

    /**
     * @param  array<array-key, mixed>  $categoryIds
     */
    public function __construct(
        public readonly string $filePath,
        string $extension = '',
        array $categoryIds = [],
        ?string $charset = null,
    ) {
        $this->extension = strtolower(ltrim(trim($extension), '.'));
        $this->categoryIds = $this->normalizeCategoryIds($categoryIds);

        $charset = trim((string) $charset);
        $this->charset = $charset === '' ? null : $charset;
    }

    /**
     * @param  array<array-key, mixed>  $categoryIds
     * @return list<int>
     */
    private function normalizeCategoryIds(array $categoryIds): array
    {
        $normalizedIds = [];

        foreach ($categoryIds as $categoryId) {
            if (! is_numeric($categoryId) || (int) $categoryId <= 0) {
                continue;
            }

            $normalizedIds[(int) $categoryId] = (int) $categoryId;
        }

        return array_values($normalizedIds);
    }
}
