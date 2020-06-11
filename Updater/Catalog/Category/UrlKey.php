<?php

declare(strict_types=1);

namespace Baldwin\UrlDataIntegrityChecker\Updater\Catalog\Category;

use Baldwin\UrlDataIntegrityChecker\Checker\Catalog\Category\UrlKey as UrlKeyChecker;
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
            $errorMsg = __('We are already refreshing the category url key\'s, just have a little patience 🙂');

            $this->metaStorage->setErrorMessage($storageIdentifier, (string) $errorMsg);
            throw new AlreadyRefreshingException($errorMsg);
        }

        $this->metaStorage->setErrorMessage($storageIdentifier, '');
        $this->metaStorage->setStartRefreshing($storageIdentifier, $initiator);

        $categoryData = $this->urlKeyChecker->execute();
        $this->storage->write($storageIdentifier, $categoryData);

        $this->metaStorage->setFinishedRefreshing($storageIdentifier);

        return $categoryData;
    }
}
