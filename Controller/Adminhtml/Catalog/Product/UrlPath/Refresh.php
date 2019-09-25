<?php

declare(strict_types=1);

namespace Baldwin\UrlDataIntegrityChecker\Controller\Adminhtml\Catalog\Product\UrlPath;

use Baldwin\UrlDataIntegrityChecker\Cron\CheckProductUrlPath as CheckProductUrlPathCron;
use Baldwin\UrlDataIntegrityChecker\Cron\ScheduleJob;
use Magento\Backend\App\Action as BackendAction;
use Magento\Backend\App\Action\Context as BackendContext;

class Refresh extends BackendAction
{
    const ADMIN_RESOURCE = 'Baldwin_UrlDataIntegrityChecker::catalog_data_integrity';

    private $scheduleJob;

    public function __construct(
        BackendContext $context,
        ScheduleJob $scheduleJob
    ) {
        parent::__construct($context);

        $this->scheduleJob = $scheduleJob;
    }

    public function execute()
    {
        $scheduled = $this->scheduleJob->schedule(CheckProductUrlPathCron::JOB_NAME);

        if ($scheduled) {
            $this->getMessageManager()->addSuccess(
                (string) __(
                    'The refresh job was scheduled, please check back in a few moments to see the updated results'
                )
            );
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
