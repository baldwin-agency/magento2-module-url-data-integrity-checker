<?php

declare(strict_types=1);

namespace Baldwin\UrlDataIntegrityChecker\Controller\Adminhtml\Catalog\Product\UrlPath;

use Magento\Backend\App\Action as BackendAction;
use Magento\Backend\App\Action\Context as BackendContext;
use Magento\Backend\Model\View\Result\Page as BackendResultPage;
use Magento\Framework\View\Result\PageFactory as ResultPageFactory;

class Index extends BackendAction
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
        /** @var BackendResultPage */
        $resultPage = $this->resultPageFactory->create();
        $resultPage->setActiveMenu('Baldwin_UrlDataIntegrityChecker::catalog_product_urlpath');
        $resultPage->getConfig()->getTitle()->prepend('Data Integrity - Product Url Path');

        return $resultPage;
    }
}
