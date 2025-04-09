<?php

declare(strict_types=1);

namespace Baldwin\UrlDataIntegrityChecker\Cron;

use Baldwin\UrlDataIntegrityChecker\Storage\Meta as MetaStorage;
use Baldwin\UrlDataIntegrityChecker\Updater\Catalog\Category\UrlPath as UrlPathUpdater;

class CheckCategoryUrlPath
{
    public const JOB_NAME = 'baldwin_urldataintegritychecker_cron_checkcategoryurlpath';

    private $urlPathUpdater;

    public function __construct(
        UrlPathUpdater $urlPathUpdater
    ) {
        $this->urlPathUpdater = $urlPathUpdater;
    }

    public function execute(): void
    {
        $this->urlPathUpdater->refresh(MetaStorage::INITIATOR_CRON);
    }
}
