<?php

declare(strict_types=1);

namespace Baldwin\UrlDataIntegrityChecker\Updater\Catalog\Product;

use Baldwin\UrlDataIntegrityChecker\Checker\Catalog\Product\UrlPath as UrlPathChecker;
use Baldwin\UrlDataIntegrityChecker\Exception\AlreadyRefreshingException;
use Baldwin\UrlDataIntegrityChecker\Storage\Cache as CacheStorage;
use Baldwin\UrlDataIntegrityChecker\Storage\Meta as MetaStorage;

class UrlPath
{
    private $urlPathChecker;
    private $storage;
    private $metaStorage;

    public function __construct(
        UrlPathChecker $urlPathChecker,
        CacheStorage $storage,
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
            throw new AlreadyRefreshingException(__('We are already refreshing the product url path\'s, just have a little patience 🙂'));
        }

        $this->metaStorage->setStartRefreshing($storageIdentifier, $initiator);

        $productData = $this->urlPathChecker->execute();
        $this->storage->write($storageIdentifier, $productData);

        $this->metaStorage->setFinishedRefreshing($storageIdentifier);

        return $productData;
    }
}
