<?php

declare(strict_types=1);

namespace Baldwin\UrlDataIntegrityChecker\Checker\Catalog\Product;

use Baldwin\UrlDataIntegrityChecker\Util\Configuration as ConfigUtil;
use Baldwin\UrlDataIntegrityChecker\Util\Stores as StoresUtil;
use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Catalog\Model\Attribute\ScopeOverriddenValueFactory as AttributeScopeOverriddenValueFactory;
use Magento\Catalog\Model\Product as ProductModel;
use Magento\Catalog\Model\Product\Visibility as ProductVisibility;
use Magento\Catalog\Model\ResourceModel\Product\Collection as ProductCollection;
use Magento\Catalog\Model\ResourceModel\Product\CollectionFactory as ProductCollectionFactory;
use Magento\Store\Model\Store;

class UrlPath
{
    const URL_PATH_ATTRIBUTE = 'url_path';
    const PROBLEM_DESCRIPTION =
        'Product has a non-null url_path attribute, this is known to cause problems with url rewrites in Magento.'
        . ' It\'s advised to remove this value from the database.';
    const STORAGE_IDENTIFIER = 'product-url-path';

    private $storesUtil;
    private $productCollectionFactory;
    private $attributeScopeOverriddenValueFactory;
    private $configUtil;

    public function __construct(
        StoresUtil $storesUtil,
        ProductCollectionFactory $productCollectionFactory,
        AttributeScopeOverriddenValueFactory $attributeScopeOverriddenValueFactory,
        ConfigUtil $configUtil
    ) {
        $this->storesUtil = $storesUtil;
        $this->productCollectionFactory = $productCollectionFactory;
        $this->attributeScopeOverriddenValueFactory = $attributeScopeOverriddenValueFactory;
        $this->configUtil = $configUtil;
    }

    /**
     * @return array<array<string, mixed>>
     */
    public function execute(): array
    {
        $productData = $this->checkForNonEmptyUrlPathAttributeValues();

        return $productData;
    }

    /**
     * @return array<array<string, mixed>>
     */
    private function checkForNonEmptyUrlPathAttributeValues(): array
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
                ->addAttributeToSelect(self::URL_PATH_ATTRIBUTE)
                ->addAttributeToFilter(self::URL_PATH_ATTRIBUTE, ['notnull' => true], $joinType)
            ;

            if ($this->configUtil->getOnlyCheckVisibleProducts()) {
                $collection->addAttributeToFilter(
                    ProductInterface::VISIBILITY,
                    ['neq' => ProductVisibility::VISIBILITY_NOT_VISIBLE]
                );
            }

            $productsWithProblems[] = $this->getProductsWithProblems($storeId, $collection);
        }

        if (!empty($productsWithProblems)) {
            $productsWithProblems = array_merge(...$productsWithProblems);
        }

        return $productsWithProblems;
    }

    /**
     * @param ProductCollection<ProductModel> $collection
     *
     * @return array<array<string, mixed>>
     */
    private function getProductsWithProblems(int $storeId, ProductCollection $collection): array
    {
        $problems = [];

        foreach ($collection as $product) {
            $isOverridden = $this
                ->attributeScopeOverriddenValueFactory
                ->create()
                ->containsValue(ProductInterface::class, $product, self::URL_PATH_ATTRIBUTE, $storeId)
            ;

            if ($isOverridden || $storeId === Store::DEFAULT_STORE_ID) {
                assert(is_numeric($product->getEntityId()));

                $problems[] = [
                    'productId' => (int) $product->getEntityId(),
                    'sku'       => $product->getSku(),
                    'storeId'   => $storeId,
                    'problem'   => self::PROBLEM_DESCRIPTION,
                ];
            }
        }

        return $problems;
    }
}
