<?php

declare(strict_types=1);

namespace Baldwin\UrlDataIntegrityChecker\Ui\DataProvider;

use Baldwin\UrlDataIntegrityChecker\Model\ResourceModel\UrlPathCollection;
use Magento\Ui\DataProvider\AbstractDataProvider;

class ProductUrlPath extends AbstractDataProvider
{
    private $urlPathCollection;

    public function __construct(
        $name,
        $primaryFieldName,
        $requestFieldName,
        UrlPathCollection $urlPathCollection,
        array $meta = [],
        array $data = []
    ) {
        parent::__construct($name, $primaryFieldName, $requestFieldName, $meta, $data);

        $this->urlPathCollection = $urlPathCollection;
    }

    public function getCollection()
    {
        return $this->urlPathCollection;
    }
}
