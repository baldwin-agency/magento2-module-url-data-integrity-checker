<?php

declare(strict_types=1);

namespace Baldwin\UrlDataIntegrityChecker\Cron;

use Baldwin\UrlDataIntegrityChecker\Checker\Catalog\Product\UrlKey as UrlKeyChecker;
use Baldwin\UrlDataIntegrityChecker\Storage\Cache as CacheStorage;

class CheckProductUrlKey
{
    const JOB_NAME = 'baldwin_urldataintegritychecker_cron_checkproducturlkey';

    private $urlKeyChecker;
    private $storage;

    public function __construct(
        UrlKeyChecker $urlKeyChecker,
        CacheStorage $storage
    ) {
        $this->urlKeyChecker = $urlKeyChecker;
        $this->storage = $storage;
    }

    public function execute()
    {
        $productData = $this->urlKeyChecker->execute();
        $this->storage->write(UrlKeyChecker::STORAGE_IDENTIFIER, $productData);
    }
}
