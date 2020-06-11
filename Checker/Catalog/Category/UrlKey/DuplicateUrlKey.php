<?php

declare(strict_types=1);

namespace Baldwin\UrlDataIntegrityChecker\Checker\Catalog\Category\UrlKey;

class DuplicateUrlKey
{
    /**
     * @return array<array<string, mixed>>
     */
    public function execute(): array
    {
        $categoryData = $this->checkForDuplicatedUrlKeyAttributeValues();

        return $categoryData;
    }

    /**
     * @return array<array<string, mixed>>
     */
    private function checkForDuplicatedUrlKeyAttributeValues(): array
    {
        $categoriesWithProblems = [];

        // TODO !!!!!

        return $categoriesWithProblems;
    }
}
