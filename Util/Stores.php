<?php

declare(strict_types=1);

namespace Baldwin\UrlDataIntegrityChecker\Util;

use Magento\Store\Model\StoreRepository;

class Stores
{
    private $storeRepository;

    public function __construct(
        StoreRepository $storeRepository
    ) {
        $this->storeRepository = $storeRepository;
    }

    public function getAllStoreIds(): array
    {
        $storeIds = [];

        $stores = $this->storeRepository->getList();
        foreach ($stores as $store) {
            $storeIds[] = (int) $store->getStoreId();
        }

        return $storeIds;
    }
}
