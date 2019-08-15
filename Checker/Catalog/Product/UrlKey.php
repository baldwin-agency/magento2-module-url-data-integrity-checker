<?php

declare(strict_types=1);

namespace Baldwin\UrlDataIntegrityChecker\Checker\Catalog\Product;

use Baldwin\UrlDataIntegrityChecker\Checker\Catalog\Product\UrlKey\DuplicateUrlKey as DuplicateUrlKeyChecker;
use Baldwin\UrlDataIntegrityChecker\Checker\Catalog\Product\UrlKey\EmptyUrlKey as EmptyUrlKeyChecker;
use Baldwin\UrlDataIntegrityChecker\Console\Progress;

class UrlKey
{
    const URL_KEY_ATTRIBUTE = 'url_key';
    const STORAGE_IDENTIFIER = 'product-url-key';

    private $duplicateUrlKeyChecker;
    private $emptyUrlKeyChecker;
    private $progress;

    public function __construct(
        DuplicateUrlKeyChecker $duplicateUrlKeyChecker,
        EmptyUrlKeyChecker $emptyUrlKeyChecker,
        Progress $progress
    ) {
        $this->duplicateUrlKeyChecker = $duplicateUrlKeyChecker;
        $this->emptyUrlKeyChecker = $emptyUrlKeyChecker;
        $this->progress = $progress;
    }

    public function execute(): array
    {
        $productData = array_merge(
            $this->duplicateUrlKeyChecker->execute(),
            $this->emptyUrlKeyChecker->execute()
        );

        return $productData;
    }
}
