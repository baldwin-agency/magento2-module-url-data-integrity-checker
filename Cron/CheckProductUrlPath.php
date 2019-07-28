<?php

declare(strict_types=1);

namespace Baldwin\UrlDataIntegrityChecker\Cron;

use Baldwin\UrlDataIntegrityChecker\Storage\Meta as MetaStorage;
use Baldwin\UrlDataIntegrityChecker\Updater\Catalog\Product\UrlPath as UrlPathUpdater;

class CheckProductUrlPath
{
    const JOB_NAME = 'baldwin_urldataintegritychecker_cron_checkproducturlpath';

    private $urlPathUpdater;

    public function __construct(
        UrlPathUpdater $urlPathUpdater
    ) {
        $this->urlPathUpdater = $urlPathUpdater;
    }

    public function execute()
    {
        $this->urlPathUpdater->refresh(MetaStorage::INITIATOR_CRON);
    }
}
