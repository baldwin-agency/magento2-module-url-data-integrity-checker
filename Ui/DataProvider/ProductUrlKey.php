<?php

declare(strict_types=1);

namespace Baldwin\UrlDataIntegrityChecker\Ui\DataProvider;

use Baldwin\UrlDataIntegrityChecker\Model\ResourceModel\UrlKeyCollection;
use Magento\Ui\DataProvider\AbstractDataProvider;

class ProductUrlKey extends AbstractDataProvider
{
    private $urlKeyCollection;

    public function __construct(
        $name,
        $primaryFieldName,
        $requestFieldName,
        UrlKeyCollection $urlKeyCollection,
        array $meta = [],
        array $data = []
    ) {
        parent::__construct($name, $primaryFieldName, $requestFieldName, $meta, $data);

        $this->urlKeyCollection = $urlKeyCollection;
    }

    public function getCollection()
    {
        return $this->urlKeyCollection;
    }
}
