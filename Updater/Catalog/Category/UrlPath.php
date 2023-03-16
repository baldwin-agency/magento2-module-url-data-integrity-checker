<?php

declare(strict_types=1);

namespace Baldwin\UrlDataIntegrityChecker\Updater\Catalog\Category;

use Baldwin\UrlDataIntegrityChecker\Checker\Catalog\Category\UrlPath as UrlPathChecker;
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

    /**
     * @return array<array<string, mixed>>
     */
    public function refresh(string $initiator): array
    {
        $storageIdentifier = UrlPathChecker::STORAGE_IDENTIFIER;

        if ($this->metaStorage->isRefreshing($storageIdentifier)) {
            $errorMsg = __('We are already refreshing the category url path\'s, just have a little patience');

            $this->metaStorage->setErrorMessage($storageIdentifier, (string) $errorMsg);
            throw new AlreadyRefreshingException($errorMsg);
        }

        $this->storage->clear($storageIdentifier);

        $this->metaStorage->setErrorMessage($storageIdentifier, '');
        $this->metaStorage->setStartRefreshing($storageIdentifier, $initiator);

        try {
            $categoryData = $this->urlPathChecker->execute();
            $this->storage->write($storageIdentifier, $categoryData);

            $this->metaStorage->setFinishedRefreshing($storageIdentifier);
        } catch (\Throwable $ex) {
            $this->metaStorage->clearStatus($storageIdentifier);
            $this->metaStorage->setErrorMessage($storageIdentifier, $ex->getMessage());

            throw $ex;
        }

        return $categoryData;
    }
}
