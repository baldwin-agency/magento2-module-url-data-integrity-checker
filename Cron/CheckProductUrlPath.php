<?php

declare(strict_types=1);

namespace Baldwin\UrlDataIntegrityChecker\Cron;

use Baldwin\UrlDataIntegrityChecker\Checker\Catalog\Product\UrlPath as UrlPathChecker;
use Baldwin\UrlDataIntegrityChecker\Storage\Cache as CacheStorage;

class CheckProductUrlPath
{
    const JOB_NAME = 'baldwin_urldataintegritychecker_cron_checkproducturlpath';

    private $urlPathChecker;
    private $storage;

    public function __construct(
        UrlPathChecker $urlPathChecker,
        CacheStorage $storage
    ) {
        $this->urlPathChecker = $urlPathChecker;
        $this->storage = $storage;
    }

    public function execute()
    {
        $productData = $this->urlPathChecker->execute();
        $this->storage->write(UrlPathChecker::STORAGE_IDENTIFIER, $productData);
    }
}
