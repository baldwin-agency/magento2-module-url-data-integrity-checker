<?php

declare(strict_types=1);

namespace Baldwin\UrlDataIntegrityChecker\Cron;

use Baldwin\UrlDataIntegrityChecker\Storage\Meta as MetaStorage;
use Baldwin\UrlDataIntegrityChecker\Updater\Catalog\Category\UrlKey as UrlKeyUpdater;

class CheckCategoryUrlKey
{
    const JOB_NAME = 'baldwin_urldataintegritychecker_cron_checkcategoryurlkey';

    private $urlKeyUpdater;

    public function __construct(
        UrlKeyUpdater $urlKeyUpdater
    ) {
        $this->urlKeyUpdater = $urlKeyUpdater;
    }

    public function execute()
    {
        $this->urlKeyUpdater->refresh(MetaStorage::INITIATOR_CRON);
    }
}
