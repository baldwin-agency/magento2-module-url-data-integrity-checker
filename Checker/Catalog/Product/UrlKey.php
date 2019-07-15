<?php

declare(strict_types=1);

namespace Baldwin\UrlDataIntegrityChecker\Checker\Catalog\Product;

use Baldwin\UrlDataIntegrityChecker\Console\Progress;
use Baldwin\UrlDataIntegrityChecker\Util\Stores as StoresUtil;
use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Catalog\Model\Attribute\ScopeOverriddenValueFactory as AttributeScopeOverriddenValueFactory;
use Magento\Catalog\Model\ResourceModel\Product\Collection as ProductCollection;
use Magento\Catalog\Model\ResourceModel\Product\CollectionFactory as ProductCollectionFactory;
use Magento\Store\Model\Store;

class UrlKey
{
    const URL_KEY_ATTRIBUTE = 'url_key';
    const EMPTY_PROBLEM_DESCRIPTION = 'Product has an empty url_key value. This needs to be fixed.';
    const DUPLICATED_PROBLEM_DESCRIPTION =
        'Product has a duplicated url_key value. It\'s the same as another product (ID: %s, Store: %s)';

    private $storesUtil;
    private $progress;
    private $productCollectionFactory;
    private $attributeScopeOverriddenValueFactory;

    private $cachedProductUrlKeyData;
    private $cachedProductSkusByIds;

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
    }

    public function execute(): array
    {
        $this->cachedProductUrlKeyData = [];
        $this->cachedProductSkusByIds = [];

        $productData = array_merge(
            $this->checkForEmptyUrlKeyAttributeValues(),
            $this->checkForDuplicatedUrlKeyAttributeValues()
        );

        return $productData;
    }

    private function checkForEmptyUrlKeyAttributeValues(): array
    {
        $productsWithProblems = [];

        $storeIds = $this->storesUtil->getAllStoreIds();
        foreach ($storeIds as $storeId) {
            // we need a left join when using the non-default store view
            // and especially for the case where storeId 0 doesn't have a value set for this attribute
            $joinType = $storeId === Store::DEFAULT_STORE_ID ? 'inner' : 'left';

            $collection = $this->productCollectionFactory->create();
            $collection
                ->setStoreId($storeId)
                ->addAttributeToSelect(self::URL_KEY_ATTRIBUTE)
                ->addAttributeToFilter([
                    [
                        'attribute' => self::URL_KEY_ATTRIBUTE,
                        'null' => true,
                    ],
                    [
                        'attribute' => self::URL_KEY_ATTRIBUTE,
                        'eq' => '',
                    ],
                ], null, $joinType)
            ;

            $productsWithProblems[] = $this->getProductsWithProblems($storeId, $collection);
        }

        if (!empty($productsWithProblems)) {
            $productsWithProblems = array_merge(...$productsWithProblems);
        }

        return $productsWithProblems;
    }

    private function checkForDuplicatedUrlKeyAttributeValues(): array
    {
        $storeIds = $this->storesUtil->getAllStoreIds();
        foreach ($storeIds as $storeId) {
            // we need a left join when using the non-default store view
            // and especially for the case where storeId 0 doesn't have a value set for this attribute
            $joinType = $storeId === Store::DEFAULT_STORE_ID ? 'inner' : 'left';

            $collection = $this->productCollectionFactory->create();
            $collection
                ->setStoreId($storeId)
                ->addAttributeToSelect(self::URL_KEY_ATTRIBUTE)
                ->addAttributeToFilter(self::URL_KEY_ATTRIBUTE, ['notnull' => true], $joinType)
                ->addAttributeToFilter(self::URL_KEY_ATTRIBUTE, ['neq' => ''], $joinType)
                // TODO: remove!
                // ->addAttributeToFilter('entity_id', [
                //     'in' => [147, 148, 149, 150, 151, 152, 153, 154, 155, 156, 157, 158],
                // ])
            ;

            $this->storeProductUrlKeyData($storeId, $collection);
        }

        $productsWithProblems = $this->getProductsWithDuplicatedUrlKeyProblems();

        return $productsWithProblems;
    }

    private function storeProductUrlKeyData(int $storeId, ProductCollection $collection): void
    {
        $progress = $this->progress->initProgressBar(
            $collection->getSize(),
            50,
            " %message%\n %current%/%max% %bar% %percent%%\n",
            "Fetching data for store $storeId"
        );

        foreach ($collection as $product) {
            $productId     = $product->getEntityId();
            $productSku    = $product->getSku();
            $productUrlKey = $product->getUrlKey();

            $dataKey = "$storeId-$productId";

            $isOverridden = $this
                ->attributeScopeOverriddenValueFactory
                ->create()
                ->containsValue(ProductInterface::class, $product, self::URL_KEY_ATTRIBUTE, $storeId)
            ;
            if ($isOverridden || $storeId === Store::DEFAULT_STORE_ID) {
                $this->cachedProductUrlKeyData[$dataKey] = $productUrlKey;
            }

            $this->cachedProductSkusByIds[$productId] = $productSku;

            $progress->advance();
        }

        $progress->setMessage("Finished fetching data for store $storeId");
        $progress->finish();
    }

    private function getProductsWithDuplicatedUrlKeyProblems(): array
    {
        $products = [];

        $storeIds = $this->storesUtil->getAllStoreIds();
        $inheritedProductUrlKeyData = [];
        foreach ($this->cachedProductUrlKeyData as $key => $urlKey) {
            list($storeId, $productId) = explode('-', $key);
            if ((int) $storeId === Store::DEFAULT_STORE_ID) {
                foreach ($storeIds as $sId) {
                    if ($sId !== Store::DEFAULT_STORE_ID
                        && !array_key_exists("$sId-$productId", $this->cachedProductUrlKeyData)) {
                        $inheritedProductUrlKeyData["$sId-$productId"] = $urlKey;
                    }
                }
            }
        }

        $urlKeysWhichExistMoreThanOnce = array_filter(
            array_count_values($this->cachedProductUrlKeyData),
            function ($count) {
                return $count > 1;
            }
        );

        $potentialDuplicatedUrlKeys = array_filter(
            $this->cachedProductUrlKeyData,
            function ($urlKey) use ($urlKeysWhichExistMoreThanOnce) {
                return array_key_exists($urlKey, $urlKeysWhichExistMoreThanOnce);
            }
        ) ?: [];

        // TODO: there is probably a more elegant solution here...
        $mappedUrlKeysWithStoreAndProductIds = [];
        foreach ($potentialDuplicatedUrlKeys as $key => $urlKey) {
            if (!array_key_exists($urlKey, $mappedUrlKeysWithStoreAndProductIds)) {
                $mappedUrlKeysWithStoreAndProductIds[$urlKey] = [];
            }
            $mappedUrlKeysWithStoreAndProductIds[$urlKey][] = $key;
        }

        foreach ($mappedUrlKeysWithStoreAndProductIds as $urlKey => $storeAndProductIds) {
            foreach ($storeAndProductIds as $storeAndProductId) {
                list($storeId, $productId) = explode('-', $storeAndProductId);

                $conflictingStoreAndProductIds = array_diff($storeAndProductIds, [$storeAndProductId]);
                foreach ($conflictingStoreAndProductIds as $conflictingStoreAndProductId) {
                    list($conflictingStoreId, $conflictingProductId) = explode('-', $conflictingStoreAndProductId);

                    if ($storeId === $conflictingStoreId) {
                        $products[] = [
                            'id'      => $productId,
                            'sku'     => $this->cachedProductSkusByIds[$productId],
                            'storeId' => $storeId,
                            'problem' => sprintf(
                                self::DUPLICATED_PROBLEM_DESCRIPTION,
                                $conflictingProductId,
                                $conflictingStoreId
                            ),
                        ];
                    // if same product id, we don't care,
                    // since it wouldn't be a conflict if they exist in another storeview
                    } elseif ($productId !== $conflictingProductId) {
                        if (array_key_exists("$conflictingStoreId-$productId", $inheritedProductUrlKeyData)
                            && $inheritedProductUrlKeyData["$conflictingStoreId-$productId"] === $urlKey
                        ) {
                            $products[] = [
                                'id'      => $productId,
                                'sku'     => $this->cachedProductSkusByIds[$productId],
                                'storeId' => $storeId,
                                'problem' => sprintf(
                                    self::DUPLICATED_PROBLEM_DESCRIPTION,
                                    $conflictingProductId,
                                    $conflictingStoreId
                                ),
                            ];
                            $products[] = [
                                'id'      => $conflictingProductId,
                                'sku'     => $this->cachedProductSkusByIds[$conflictingProductId],
                                'storeId' => $conflictingStoreId,
                                'problem' => sprintf(
                                    self::DUPLICATED_PROBLEM_DESCRIPTION,
                                    $productId,
                                    $storeId
                                ),
                            ];
                        }
                    }
                }
            }
        }

        return $products;
    }

    private function getProductsWithProblems(int $storeId, ProductCollection $collection): array
    {
        $products = [];

        foreach ($collection as $product) {
            $isOverridden = $this
                ->attributeScopeOverriddenValueFactory
                ->create()
                ->containsValue(ProductInterface::class, $product, self::URL_KEY_ATTRIBUTE, $storeId)
            ;

            if ($isOverridden || $storeId === Store::DEFAULT_STORE_ID) {
                $products[] = [
                    'id'      => $product->getEntityId(),
                    'sku'     => $product->getSku(),
                    'storeId' => $storeId,
                    'problem' => self::EMPTY_PROBLEM_DESCRIPTION,
                ];
            }
        }

        return $products;
    }
}
