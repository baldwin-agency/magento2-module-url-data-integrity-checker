<?php

declare(strict_types=1);

namespace Baldwin\UrlDataIntegrityChecker\Updater\Catalog\Product;

use Baldwin\UrlDataIntegrityChecker\Checker\Catalog\Product\UrlKey as UrlKeyChecker;
use Baldwin\UrlDataIntegrityChecker\Exception\AlreadyRefreshingException;
use Baldwin\UrlDataIntegrityChecker\Storage\Cache as CacheStorage;
use Baldwin\UrlDataIntegrityChecker\Storage\Meta as MetaStorage;

class UrlKey
{
    private $urlKeyChecker;
    private $storage;
    private $metaStorage;

    public function __construct(
        UrlKeyChecker $urlKeyChecker,
        CacheStorage $storage,
        MetaStorage $metaStorage
    ) {
        $this->urlKeyChecker = $urlKeyChecker;
        $this->storage = $storage;
        $this->metaStorage = $metaStorage;
    }

    public function refresh(string $initiator): array
    {
        $storageIdentifier = UrlKeyChecker::STORAGE_IDENTIFIER;

        if ($this->metaStorage->isRefreshing($storageIdentifier)) {
            $errorMsg = __('We are already refreshing the product url key\'s, just have a little patience 🙂');

            $this->metaStorage->setErrorMessage($storageIdentifier, (string) $errorMsg);
            throw new AlreadyRefreshingException($errorMsg);
        }

        $this->metaStorage->setErrorMessage($storageIdentifier, '');
        $this->metaStorage->setStartRefreshing($storageIdentifier, $initiator);

        $productData = $this->urlKeyChecker->execute();
        $this->storage->write($storageIdentifier, $productData);

        $this->metaStorage->setFinishedRefreshing($storageIdentifier);

        return $productData;
    }
}
