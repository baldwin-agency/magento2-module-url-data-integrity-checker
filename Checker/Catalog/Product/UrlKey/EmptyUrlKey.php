<?php

declare(strict_types=1);

namespace Baldwin\UrlDataIntegrityChecker\Checker\Catalog\Product\UrlKey;

use Baldwin\UrlDataIntegrityChecker\Checker\Catalog\Product\UrlKey as UrlKeyChecker;
use Baldwin\UrlDataIntegrityChecker\Console\Progress;
use Baldwin\UrlDataIntegrityChecker\Util\Stores as StoresUtil;
use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Catalog\Model\Attribute\ScopeOverriddenValueFactory as AttributeScopeOverriddenValueFactory;
use Magento\Catalog\Model\ResourceModel\Product\Collection as ProductCollection;
use Magento\Catalog\Model\ResourceModel\Product\CollectionFactory as ProductCollectionFactory;
use Magento\Store\Model\Store;

class EmptyUrlKey
{
    const EMPTY_PROBLEM_DESCRIPTION = 'Product has an empty url_key value. This needs to be fixed.';

    private $storesUtil;
    private $progress;
    private $progressIndex;
    private $productCollectionFactory;
    private $attributeScopeOverriddenValueFactory;

    public function __construct(
        StoresUtil $storesUtil,
        Progress $progress,
        ProductCollectionFactory $productCollectionFactory,
        AttributeScopeOverriddenValueFactory $attributeScopeOverriddenValueFactory
    ) {
        $this->storesUtil = $storesUtil;
        $this->progress = $progress;
        $this->productCollectionFactory = $productCollectionFactory;
        $this->attributeScopeOverriddenValueFactory = $attributeScopeOverriddenValueFactory;

        $this->progressIndex = 0;
    }

    public function execute(): array
    {
        $productData = $this->checkForEmptyUrlKeyAttributeValues();

        return $productData;
    }

    private function checkForEmptyUrlKeyAttributeValues(): array
    {
        $productsWithProblems = [];

        $this->progress->initProgressBar(
            50,
            " %message%\n %current%/%max% %bar% %percent%%\n",
            'Preparing fecthing empty url key data'
        );
        $this->progressIndex = 0;

        $storeIds = $this->storesUtil->getAllStoreIds();
        foreach ($storeIds as $storeId) {
            // we need a left join when using the non-default store view
            // and especially for the case where storeId 0 doesn't have a value set for this attribute
            $joinType = $storeId === Store::DEFAULT_STORE_ID ? 'inner' : 'left';

            $collection = $this->productCollectionFactory->create();
            $collection
                ->setStoreId($storeId)
                ->addAttributeToSelect(UrlKeyChecker::URL_KEY_ATTRIBUTE)
                ->addAttributeToFilter([
                    [
                        'attribute' => UrlKeyChecker::URL_KEY_ATTRIBUTE,
                        'null' => true,
                    ],
                    [
                        'attribute' => UrlKeyChecker::URL_KEY_ATTRIBUTE,
                        'eq' => '',
                    ],
                ], null, $joinType)
            ;

            if ($this->progressIndex === 0) {
                $this->progress->setGuestimatedSize(count($storeIds), $collection->getSize());
            }

            $this->progress->updateExpectedSize($this->progressIndex++, $collection->getSize());
            $this->progress->setMessage("Fetching empty url key data for store $storeId");

            $productsWithProblems[] = $this->getProductsWithProblems($storeId, $collection);

            $this->progress->setMessage("Finished empty url key fetching data for store $storeId");
        }

        $this->progress->finish();

        if (!empty($productsWithProblems)) {
            $productsWithProblems = array_merge(...$productsWithProblems);
        }

        return $productsWithProblems;
    }

    private function getProductsWithProblems(int $storeId, ProductCollection $collection): array
    {
        $problems = [];

        foreach ($collection as $product) {
            $isOverridden = $this
                ->attributeScopeOverriddenValueFactory
                ->create()
                ->containsValue(ProductInterface::class, $product, UrlKeyChecker::URL_KEY_ATTRIBUTE, $storeId)
            ;

            if ($isOverridden || $storeId === Store::DEFAULT_STORE_ID) {
                $problems[] = [
                    'productId' => (int) $product->getEntityId(),
                    'sku'       => $product->getSku(),
                    'storeId'   => $storeId,
                    'problem'   => self::EMPTY_PROBLEM_DESCRIPTION,
                ];
            }

            $this->progress->advance();
        }

        return $problems;
    }
}
