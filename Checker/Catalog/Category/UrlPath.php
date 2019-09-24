<?php

declare(strict_types=1);

namespace Baldwin\UrlDataIntegrityChecker\Checker\Catalog\Category;

use Baldwin\UrlDataIntegrityChecker\Util\Stores as StoresUtil;
use Magento\Catalog\Api\Data\CategoryInterface;
use Magento\Catalog\Model\Attribute\ScopeOverriddenValueFactory as AttributeScopeOverriddenValueFactory;
use Magento\Catalog\Model\Category;
use Magento\Catalog\Model\ResourceModel\Category\Collection as CategoryCollection;
use Magento\Catalog\Model\ResourceModel\Category\CollectionFactory as CategoryCollectionFactory;
use Magento\Framework\Exception\LocalizedException;
use Magento\Store\Model\Store;

class UrlPath
{
    const URL_PATH_ATTRIBUTE = 'url_path';
    const URL_PATH_SEPARATOR = '/';
    const PROBLEM_DESCRIPTION = 'Category has an incorrect url_path value "%s". It should be "%s"';
    const STORAGE_IDENTIFIER = 'category-url-path';

    private $storesUtil;
    private $categoryCollectionFactory;
    private $attributeScopeOverriddenValueFactory;

    private $calculatedUrlPathPerCategoryAndStoreId;

    public function __construct(
        StoresUtil $storesUtil,
        CategoryCollectionFactory $categoryCollectionFactory,
        AttributeScopeOverriddenValueFactory $attributeScopeOverriddenValueFactory
    ) {
        $this->storesUtil = $storesUtil;
        $this->categoryCollectionFactory = $categoryCollectionFactory;
        $this->attributeScopeOverriddenValueFactory = $attributeScopeOverriddenValueFactory;
    }

    public function execute(): array
    {
        $categoryData = $this->checkForIncorrectUrlPathAttributeValues();

        return $categoryData;
    }

    public function checkForIncorrectUrlPathAttributeValues(): array
    {
        $problems = [];

        $storeIds = $this->storesUtil->getAllStoreIds();
        foreach ($storeIds as $storeId) {
            $allCategories = $this->getAllVisibleCategoriesWithStoreId($storeId);

            foreach ($allCategories as $category) {
                $isOverridden = $this
                    ->attributeScopeOverriddenValueFactory
                    ->create()
                    ->containsValue(CategoryInterface::class, $category, self::URL_PATH_ATTRIBUTE, $storeId)
                ;

                // we don't care about non overwritten values
                if (!$isOverridden && $storeId !== Store::DEFAULT_STORE_ID) {
                    continue;
                }

                if (!$this->doesCategoryUrlPathMatchCalculatedUrlPath($category, $storeId)) {
                    $correctUrlPath = $this->getCalculatedUrlPathForCategory($category, $storeId);

                    $problems[] = [
                        'catId'   => (int) $category->getId(),
                        'name'    => $category->getName(),
                        'storeId' => $storeId,
                        'problem' => sprintf(self::PROBLEM_DESCRIPTION, $category->getUrlPath(), $correctUrlPath),
                    ];
                }
            }
        }

        return $problems;
    }

    private function getAllVisibleCategoriesWithStoreId($storeId): CategoryCollection
    {
        $categories = $this->categoryCollectionFactory->create()
            ->addAttributeToSelect('name')
            ->addAttributeToSelect('url_path')
            ->addAttributeToSelect('url_key')
            ->addAttributeToFilter('level', ['gt' => 1]) // categories with levels 0 or 1 aren't used in the frontend
            ->setStore($storeId);

        return $categories;
    }

    private function doesCategoryUrlPathMatchCalculatedUrlPath(Category $category, int $storeId): bool
    {
        $calculatedUrlPath = $this->getCalculatedUrlPathForCategory($category, $storeId);
        $currentUrlPath = $category->getUrlPath();

        return $calculatedUrlPath === $currentUrlPath;
    }

    private function getCalculatedUrlPathForCategory(Category $category, int $storeId): string
    {
        if ($this->calculatedUrlPathPerCategoryAndStoreId === null) {
            $this->fetchAllCategoriesWithUrlPathCalculatedByUrlKey();
        }

        $categoryId = (int) $category->getId();
        $key = $this->getArrayKeyForCategoryAndStoreId($categoryId, $storeId);

        if (array_key_exists($key, $this->calculatedUrlPathPerCategoryAndStoreId)) {
            return $this->calculatedUrlPathPerCategoryAndStoreId[$key];
        }

        throw new LocalizedException(__(
            "Can't find calculated url path for category id: '$categoryId' and store id: '$storeId'"
        ));
    }

    private function fetchAllCategoriesWithUrlPathCalculatedByUrlKey()
    {
        $this->calculatedUrlPathPerCategoryAndStoreId = [];

        $invisibleRootIds = $this->getAllInvisibleRootIds();
        $storeIds = $this->storesUtil->getAllStoreIds();
        foreach ($storeIds as $storeId) {
            $tempCatData = [];

            $allCategories = $this->getAllVisibleCategoriesWithStoreId($storeId);
            foreach ($allCategories as $category) {
                $categoryId = (int) $category->getId();

                $path = $category->getPath();
                foreach ($invisibleRootIds as $rootId) {
                    $path = preg_replace('#^' . preg_quote($rootId) . self::URL_PATH_SEPARATOR . '#', '', $path);
                }

                $tempCatData[$categoryId] = [
                    'url_key' => $category->getUrlKey(),
                    'path'    => $path,
                ];
            }

            foreach ($tempCatData as $catId => $catData) {
                $explodedPath = explode(self::URL_PATH_SEPARATOR, $catData['path']);

                $calculatedUrlPath = [];
                foreach ($explodedPath as $id) {
                    $id = (int) $id;

                    if (array_key_exists($id, $tempCatData)) {
                        $calculatedUrlPath[] = $tempCatData[$id]['url_key'];
                    } else {
                        throw new LocalizedException(__("Can't find category with id: '$id'"));
                    }
                }

                $key = $this->getArrayKeyForCategoryAndStoreId($catId, $storeId);
                $this->calculatedUrlPathPerCategoryAndStoreId[$key] =
                    implode(self::URL_PATH_SEPARATOR, $calculatedUrlPath);
            }
        }
    }

    private function getAllInvisibleRootIds(): array
    {
        $categoryIds = $this->categoryCollectionFactory->create()
            ->addAttributeToSelect('name')
            ->addAttributeToSelect('url_path')
            ->addAttributeToSelect('url_key')
            ->addAttributeToFilter('level', ['lteq' => 1])
            ->getAllIds();

        return $categoryIds;
    }

    private function getArrayKeyForCategoryAndStoreId(int $categoryId, int $storeId): string
    {
        return "$categoryId-$storeId";
    }
}
