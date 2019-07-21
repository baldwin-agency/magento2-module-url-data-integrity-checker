<?php

declare(strict_types=1);

namespace Baldwin\UrlDataIntegrityChecker\Model\ResourceModel;

use Baldwin\UrlDataIntegrityChecker\Checker\Catalog\Product\UrlKey as UrlKeyChecker;
use Baldwin\UrlDataIntegrityChecker\Storage\Cache as CacheStorage;
use Magento\Framework\Data\Collection as DataCollection;
use Magento\Framework\Data\Collection\EntityFactoryInterface;
use Magento\Framework\DataObject;

class UrlKeyCollection extends DataCollection
{
    private $storage;

    public function __construct(
        EntityFactoryInterface $entityFactory,
        CacheStorage $storage
    ) {
        parent::__construct($entityFactory);

        $this->storage = $storage;
    }

    public function loadData($printQuery = false, $logQuery = false)
    {
        if (!$this->isLoaded()) {
            $urlKeys = $this->storage->read(UrlKeyChecker::STORAGE_IDENTIFIER);
            foreach ($urlKeys as $urlKey) {
                $obj = new DataObject();
                $obj->setHash($urlKey['hash']);
                $obj->setProductId($urlKey['productId']);
                $obj->setStoreId($urlKey['storeId']);
                $obj->setSku($urlKey['sku']);
                $obj->setProblem($urlKey['problem']);

                $this->addItem($obj);
            }

            $this->_setIsLoaded();
        }

        return $this;
    }

    public function addOrder($field, $direction)
    {
        $this->setOrder($field, $direction); // this doesn't do anything yet I think
    }
}
