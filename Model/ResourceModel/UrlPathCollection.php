<?php

declare(strict_types=1);

namespace Baldwin\UrlDataIntegrityChecker\Model\ResourceModel;

use Baldwin\UrlDataIntegrityChecker\Checker\Catalog\Product\UrlPath as UrlPathChecker;
use Baldwin\UrlDataIntegrityChecker\Storage\Cache as CacheStorage;
use Magento\Framework\Data\Collection as DataCollection;
use Magento\Framework\Data\Collection\EntityFactoryInterface;
use Magento\Framework\DataObject;

class UrlPathCollection extends DataCollection
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
            $urlPaths = $this->storage->read(UrlPathChecker::STORAGE_IDENTIFIER);
            foreach ($urlPaths as $urlPath) {
                $obj = new DataObject();
                $obj->setId($urlPath['id'] . '-' . $urlPath['storeId']);
                $obj->setProductId($urlPath['id']);
                $obj->setStoreId($urlPath['storeId']);
                $obj->setSku($urlPath['sku']);
                $obj->setProblem($urlPath['problem']);

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
