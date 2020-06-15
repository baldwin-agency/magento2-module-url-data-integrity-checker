<?php

declare(strict_types=1);

namespace Baldwin\UrlDataIntegrityChecker\Checker\Catalog\Category\UrlKey;

use Baldwin\UrlDataIntegrityChecker\Checker\Catalog\Category\UrlKey as UrlKeyChecker;
use Baldwin\UrlDataIntegrityChecker\Util\Stores as StoresUtil;
use Magento\Catalog\Api\Data\CategoryInterface;
use Magento\Catalog\Model\Attribute\ScopeOverriddenValueFactory as AttributeScopeOverriddenValueFactory;
use Magento\Catalog\Model\Category as CategoryModel;
use Magento\Catalog\Model\ResourceModel\Category\Collection as CategoryCollection;
use Magento\Catalog\Model\ResourceModel\Category\CollectionFactory as CategoryCollectionFactory;
use Magento\Store\Model\Store;

class EmptyUrlKey
{
    const EMPTY_PROBLEM_DESCRIPTION = 'Category has an empty url_key value. This needs to be fixed.';

    private $storesUtil;
    private $categoryCollectionFactory;
    private $attributeScopeOverriddenValueFactory;

    public function __construct(
        StoresUtil $storesUtil,
        CategoryCollectionFactory $categoryCollectionFactory,
        AttributeScopeOverriddenValueFactory $attributeScopeOverriddenValueFactory
    ) {
        $this->storesUtil = $storesUtil;
        $this->categoryCollectionFactory = $categoryCollectionFactory;
        $this->attributeScopeOverriddenValueFactory = $attributeScopeOverriddenValueFactory;
    }

    /**
     * @return array<array<string, mixed>>
     */
    public function execute(): array
    {
        $categoryData = $this->checkForEmptyUrlKeyAttributeValues();

        return $categoryData;
    }

    /**
     * @return array<array<string, mixed>>
     */
    private function checkForEmptyUrlKeyAttributeValues(): array
    {
        $categoriesWithProblems = [];

        $storeIds = $this->storesUtil->getAllStoreIds();
        foreach ($storeIds as $storeId) {
            // we need a left join when using the non-default store view
            // and especially for the case where storeId 0 doesn't have a value set for this attribute
            $joinType = $storeId === Store::DEFAULT_STORE_ID ? 'inner' : 'left';

            $collection = $this->categoryCollectionFactory->create();
            $collection
                ->setStoreId($storeId)
                ->addAttributeToSelect(UrlKeyChecker::URL_KEY_ATTRIBUTE)
                ->addAttributeToSelect('name')
                ->addAttributeToFilter('entity_id', ['neq' => CategoryModel::TREE_ROOT_ID])
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

            $categoriesWithProblems[] = $this->getCategoriesWithProblems($storeId, $collection);
        }

        if (!empty($categoriesWithProblems)) {
            $categoriesWithProblems = array_merge(...$categoriesWithProblems);
        }

        return $categoriesWithProblems;
    }

    /**
     * @param CategoryCollection<CategoryModel> $collection
     *
     * @return array<array<string, mixed>>
     */
    private function getCategoriesWithProblems(int $storeId, CategoryCollection $collection): array
    {
        $problems = [];

        foreach ($collection as $category) {
            $isOverridden = $this
                ->attributeScopeOverriddenValueFactory
                ->create()
                ->containsValue(CategoryInterface::class, $category, UrlKeyChecker::URL_KEY_ATTRIBUTE, $storeId)
            ;

            if ($isOverridden || $storeId === Store::DEFAULT_STORE_ID) {
                $problems[] = [
                    'catId'   => (int) $category->getEntityId(),
                    'name'    => $category->getName(),
                    'storeId' => $storeId,
                    'problem' => self::EMPTY_PROBLEM_DESCRIPTION,
                ];
            }
        }

        return $problems;
    }
}
