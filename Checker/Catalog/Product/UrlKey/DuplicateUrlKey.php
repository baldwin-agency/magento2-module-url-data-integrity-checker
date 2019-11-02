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

class DuplicateUrlKey
{
    const DUPLICATED_PROBLEM_DESCRIPTION =
          'Product has a duplicated url_key value (%s). It\'s the same as another product (ID: %s, Store: %s).'
        . ' Please fix because this will cause problems.';

    private $storesUtil;
    private $progress;
    private $progressIndex;
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

        $productData = $this->checkForDuplicatedUrlKeyAttributeValues();

        return $productData;
    }

    private function checkForDuplicatedUrlKeyAttributeValues(): array
    {
        $this->progress->initProgressBar(
            50,
            " %message%\n %current%/%max% %bar% %percent%%\n",
            'Preparing fecthing duplicated url key data'
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
                ->addAttributeToFilter(UrlKeyChecker::URL_KEY_ATTRIBUTE, ['notnull' => true], $joinType)
                ->addAttributeToFilter(UrlKeyChecker::URL_KEY_ATTRIBUTE, ['neq' => ''], $joinType)
                // TODO: remove!
                // ->addAttributeToFilter('entity_id', [
                //     'in' => [147, 148, 149, 150, 151, 152, 153, 154, 155, 156, 157, 158],
                // ])
            ;

            if ($this->progressIndex === 0) {
                $this->progress->setGuestimatedSize(count($storeIds), $collection->getSize());
            }

            $this->progress->updateExpectedSize($this->progressIndex++, $collection->getSize());
            $this->progress->setMessage("Fetching duplicated url key data for store $storeId");

            $this->storeProductUrlKeyData($storeId, $collection);

            $this->progress->setMessage("Finished duplicated url key fetching data for store $storeId");
        }

        $this->progress->finish();

        $productsWithProblems = $this->getProductsWithDuplicatedUrlKeyProblems();

        return $productsWithProblems;
    }

    private function storeProductUrlKeyData(int $storeId, ProductCollection $collection)
    {
        foreach ($collection as $product) {
            $productId     = $product->getEntityId();
            $productSku    = $product->getSku();
            $productUrlKey = $product->getUrlKey();

            $dataKey = "$storeId-$productId";

            // TODO: this is very slow, maybe there is a better way to determine this...
            // (yes yes, raw sql queries, *sigh*)
            $isOverridden = $this
                ->attributeScopeOverriddenValueFactory
                ->create()
                ->containsValue(ProductInterface::class, $product, UrlKeyChecker::URL_KEY_ATTRIBUTE, $storeId)
            ;
            if ($isOverridden || $storeId === Store::DEFAULT_STORE_ID) {
                $this->cachedProductUrlKeyData[$dataKey] = $productUrlKey;
            }

            $this->cachedProductSkusByIds[$productId] = $productSku;

            $this->progress->advance();
        }
    }

    private function getProductsWithDuplicatedUrlKeyProblems(): array
    {
        $problems = [];

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
                $storeId   = (int) $storeId;
                $productId = (int) $productId;

                $conflictingStoreAndProductIds = array_diff($storeAndProductIds, [$storeAndProductId]);
                foreach ($conflictingStoreAndProductIds as $conflictingStoreAndProductId) {
                    list($conflictingStoreId, $conflictingProductId) = explode('-', $conflictingStoreAndProductId);
                    $conflictingStoreId   = (int) $conflictingStoreId;
                    $conflictingProductId = (int) $conflictingProductId;

                    if ($storeId === $conflictingStoreId) {
                        $problems[] = [
                            'productId' => $productId,
                            'sku'       => $this->cachedProductSkusByIds[$productId],
                            'storeId'   => $storeId,
                            'problem'   => sprintf(
                                self::DUPLICATED_PROBLEM_DESCRIPTION,
                                $urlKey,
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
                            $problems[] = [
                                'productId' => $productId,
                                'sku'       => $this->cachedProductSkusByIds[$productId],
                                'storeId'   => $storeId,
                                'problem'   => sprintf(
                                    self::DUPLICATED_PROBLEM_DESCRIPTION,
                                    $urlKey,
                                    $conflictingProductId,
                                    $conflictingStoreId
                                ),
                            ];
                            $problems[] = [
                                'productId' => $conflictingProductId,
                                'sku'       => $this->cachedProductSkusByIds[$conflictingProductId],
                                'storeId'   => $conflictingStoreId,
                                'problem'   => sprintf(
                                    self::DUPLICATED_PROBLEM_DESCRIPTION,
                                    $urlKey,
                                    $productId,
                                    $storeId
                                ),
                            ];
                        }
                    }
                }
            }
        }

        return $problems;
    }
}
