<?php

declare(strict_types=1);

namespace Baldwin\UrlDataIntegrityChecker\Checker\Catalog\Category;

use Baldwin\UrlDataIntegrityChecker\Checker\Catalog\Category\UrlKey\DuplicateUrlKey as DuplicateUrlKeyChecker;
use Baldwin\UrlDataIntegrityChecker\Checker\Catalog\Category\UrlKey\EmptyUrlKey as EmptyUrlKeyChecker;

class UrlKey
{
    const URL_KEY_ATTRIBUTE = 'url_key';
    const STORAGE_IDENTIFIER = 'category-url-key';

    private $duplicateUrlKeyChecker;
    private $emptyUrlKeyChecker;

    public function __construct(
        DuplicateUrlKeyChecker $duplicateUrlKeyChecker,
        EmptyUrlKeyChecker $emptyUrlKeyChecker
    ) {
        $this->duplicateUrlKeyChecker = $duplicateUrlKeyChecker;
        $this->emptyUrlKeyChecker = $emptyUrlKeyChecker;
    }

    /**
     * @return array<array<string, mixed>>
     */
    public function execute(): array
    {
        $categoryData = array_merge(
            $this->duplicateUrlKeyChecker->execute(),
            $this->emptyUrlKeyChecker->execute()
        );

        return $categoryData;
    }
}
