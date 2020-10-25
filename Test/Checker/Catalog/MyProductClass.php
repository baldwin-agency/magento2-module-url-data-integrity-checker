<?php

declare(strict_types=1);

namespace Baldwin\UrlDataIntegrityChecker\Test\Checker\Catalog;

use Magento\Catalog\Model\Product as ProductModel;

class MyProductClass extends ProductModel
{
    public function getSku()
    {
        return $this->getData('sku');
    }
}
