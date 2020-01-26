<?php

declare(strict_types=1);

namespace Baldwin\UrlDataIntegrityChecker\Controller\Adminhtml\Catalog\Category\UrlPath;

use Baldwin\UrlDataIntegrityChecker\Checker\Catalog\Category\UrlPath as UrlPathChecker;
use Baldwin\UrlDataIntegrityChecker\Cron\CheckCategoryUrlPath as CheckCategoryUrlPathCron;
use Baldwin\UrlDataIntegrityChecker\Cron\ScheduleJob;
use Baldwin\UrlDataIntegrityChecker\Exception\AlreadyRefreshingException;
use Baldwin\UrlDataIntegrityChecker\Storage\Meta as MetaStorage;
use Magento\Backend\App\Action as BackendAction;
use Magento\Backend\App\Action\Context as BackendContext;

class Refresh extends BackendAction
{
    const ADMIN_RESOURCE = 'Baldwin_UrlDataIntegrityChecker::catalog_data_integrity';

    private $scheduleJob;
    private $metaStorage;

    public function __construct(
        BackendContext $context,
        ScheduleJob $scheduleJob,
        MetaStorage $metaStorage
    ) {
        parent::__construct($context);

        $this->scheduleJob = $scheduleJob;
        $this->metaStorage = $metaStorage;
    }

    public function execute()
    {
        $scheduled = $this->scheduleJob->schedule(CheckCategoryUrlPathCron::JOB_NAME);

        if ($scheduled) {
            $this->getMessageManager()->addSuccess(
                (string) __(
                    'The refresh job was scheduled, please check back in a few moments to see the updated results'
                )
            );

            try {
                $storageIdentifier = UrlPathChecker::STORAGE_IDENTIFIER;
                $this->metaStorage->setPending($storageIdentifier, MetaStorage::INITIATOR_CRON);
            } catch (AlreadyRefreshingException $ex) {
                $this->getMessageManager()->addError($ex->getMessage());
            }
        } else {
            $this->getMessageManager()->addError(
                (string) __('Couldn\'t schedule refreshing due to some unknown error')
            );
        }

        $redirect = $this->resultRedirectFactory->create();
        $redirect->setRefererUrl();

        return $redirect;
    }
}
