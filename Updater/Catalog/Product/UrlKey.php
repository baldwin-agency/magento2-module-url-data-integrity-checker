<?php

declare(strict_types=1);

namespace Baldwin\UrlDataIntegrityChecker\Updater\Catalog\Product;

use Baldwin\UrlDataIntegrityChecker\Checker\Catalog\Product\UrlKey as UrlKeyChecker;
use Baldwin\UrlDataIntegrityChecker\Exception\AlreadyRefreshingException;
use Baldwin\UrlDataIntegrityChecker\Storage\Meta as MetaStorage;
use Baldwin\UrlDataIntegrityChecker\Storage\StorageInterface;

class UrlKey
{
    private $urlKeyChecker;
    private $storage;
    private $metaStorage;

    public function __construct(
        UrlKeyChecker $urlKeyChecker,
        StorageInterface $storage,
        MetaStorage $metaStorage
    ) {
        $this->urlKeyChecker = $urlKeyChecker;
        $this->storage = $storage;
        $this->metaStorage = $metaStorage;
    }

    /**
     * @return array<array<string, mixed>>
     */
    public function refresh(string $initiator): array
    {
        $storageIdentifier = UrlKeyChecker::STORAGE_IDENTIFIER;

        if ($this->metaStorage->isRefreshing($storageIdentifier)) {
            $errorMsg = __('We are already refreshing the product url key\'s, just have a little patience 🙂');

            $this->metaStorage->setErrorMessage($storageIdentifier, (string) $errorMsg);
            throw new AlreadyRefreshingException($errorMsg);
        }

        $this->storage->clear($storageIdentifier);

        $this->metaStorage->setErrorMessage($storageIdentifier, '');
        $this->metaStorage->setStartRefreshing($storageIdentifier, $initiator);

        try {
            $productData = $this->urlKeyChecker->execute();
            $this->storage->write($storageIdentifier, $productData);

            $this->metaStorage->setFinishedRefreshing($storageIdentifier);
        } catch (\Throwable $ex) {
            $this->metaStorage->clearStatus($storageIdentifier);
            $this->metaStorage->setErrorMessage($storageIdentifier, $ex->getMessage());

            throw $ex;
        }

        return $productData;
    }
}
