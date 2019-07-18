<?php

declare(strict_types=1);

namespace Baldwin\UrlDataIntegrityChecker\Controller\Adminhtml\Product;

use Magento\Backend\App\Action as BackendAction;
use Magento\Backend\App\Action\Context as BackendContext;
use Magento\Framework\View\Result\PageFactory as ResultPageFactory;

class UrlPath extends BackendAction
{
    const ADMIN_RESOURCE = 'Baldwin_UrlDataIntegrityChecker::catalog_data_integrity';

    private $resultPageFactory;

    public function __construct(
        BackendContext $context,
        ResultPageFactory $resultPageFactory
    ) {
        parent::__construct($context);

        $this->resultPageFactory = $resultPageFactory;
    }

    public function execute()
    {
        $resultPage = $this->resultPageFactory->create();

        return $resultPage;
    }
}
