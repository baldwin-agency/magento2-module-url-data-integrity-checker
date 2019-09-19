<?php

declare(strict_types=1);

namespace Baldwin\UrlDataIntegrityChecker\Updater\Catalog\Product;

use Baldwin\UrlDataIntegrityChecker\Checker\Catalog\Product\UrlPath as UrlPathChecker;
use Baldwin\UrlDataIntegrityChecker\Exception\AlreadyRefreshingException;
use Baldwin\UrlDataIntegrityChecker\Storage\Meta as MetaStorage;
use Baldwin\UrlDataIntegrityChecker\Storage\StorageInterface;

class UrlPath
{
    private $urlPathChecker;
    private $storage;
    private $metaStorage;

    public function __construct(
        UrlPathChecker $urlPathChecker,
        StorageInterface $storage,
        MetaStorage $metaStorage
    ) {
        $this->urlPathChecker = $urlPathChecker;
        $this->storage = $storage;
        $this->metaStorage = $metaStorage;
    }

    public function refresh(string $initiator): array
    {
        $storageIdentifier = UrlPathChecker::STORAGE_IDENTIFIER;

        if ($this->metaStorage->isRefreshing($storageIdentifier)) {
            $errorMsg = __('We are already refreshing the product url key\'s, just have a little patience 🙂');

            $this->metaStorage->setErrorMessage($storageIdentifier, (string) $errorMsg);
            throw new AlreadyRefreshingException($errorMsg);
        }

        $this->metaStorage->setErrorMessage($storageIdentifier, '');
        $this->metaStorage->setStartRefreshing($storageIdentifier, $initiator);

        $productData = $this->urlPathChecker->execute();
        $this->storage->write($storageIdentifier, $productData);

        $this->metaStorage->setFinishedRefreshing($storageIdentifier);

        return $productData;
    }
}
