<?php

declare(strict_types=1);

namespace Baldwin\UrlDataIntegrityChecker\Checker\Catalog\Category\UrlKey;

use Baldwin\UrlDataIntegrityChecker\Checker\Catalog\Category\UrlPath as UrlPathChecker;
use Baldwin\UrlDataIntegrityChecker\Util\Stores as StoresUtil;
use Magento\Catalog\Model\Category;

class DuplicateUrlKey
{
    const DUPLICATED_PROBLEM_DESCRIPTION =
          '%s categories were found which have a duplicated url_key value: "%s" within the same parent.'
        . ' Please fix because this will cause problems.';

    private $storesUtil;
    private $urlPathChecker;

    /** @var array<string, array<Category>> */
    private $urlPathsInfo;

    public function __construct(
        StoresUtil $storesUtil,
        UrlPathChecker $urlPathChecker
    ) {
        $this->storesUtil = $storesUtil;
        $this->urlPathChecker = $urlPathChecker;
        $this->urlPathsInfo = [];
    }

    /**
     * @return array<array<string, mixed>>
     */
    public function execute(): array
    {
        $categoryData = $this->checkForDuplicatedUrlKeyAttributeValues();

        return $categoryData;
    }

    /**
     * @return array<array<string, mixed>>
     */
    private function checkForDuplicatedUrlKeyAttributeValues(): array
    {
        $categoriesWithProblems = [];

        $storeIds = $this->storesUtil->getAllStoreIds();
        foreach ($storeIds as $storeId) {
            $categoryUrlPaths = $this->getCategoryUrlPathsByStoreId($storeId);
            $urlPathsCount = array_count_values($categoryUrlPaths);

            foreach ($urlPathsCount as $urlPath => $count) {
                if ($count === 1) {
                    continue;
                }

                $categories = $this->urlPathsInfo[$urlPath];

                foreach ($categories as $category) {
                    assert(is_numeric($category->getEntityId()));

                    $categoriesWithProblems[] = [
                        'catId'   => (int) $category->getEntityId(),
                        'name'    => $category->getName(),
                        'storeId' => $storeId,
                        'problem' => sprintf(
                            self::DUPLICATED_PROBLEM_DESCRIPTION,
                            $count,
                            $category->getUrlKey()
                        ),
                    ];
                }
            }
        }

        return $categoriesWithProblems;
    }

    /**
     * @return array<string>
     */
    private function getCategoryUrlPathsByStoreId(int $storeId): array
    {
        $this->urlPathsInfo = [];

        $urlPaths = [];

        $categories = $this->urlPathChecker->getAllVisibleCategoriesWithStoreId($storeId);
        foreach ($categories as $category) {
            $urlPath = $this->urlPathChecker->getCalculatedUrlPathForCategory($category, $storeId);

            $rootCatId = 0;
            $path = $category->getPath() ?: '';
            if (preg_match('#^(\d+)/(\d+)/.+#', $path, $matches) === 1) {
                $rootCatId = $matches[2];
            }

            $urlPath = $rootCatId . UrlPathChecker::URL_PATH_SEPARATOR . $urlPath;

            $urlPaths[] = $urlPath;
            $this->urlPathsInfo[$urlPath][] = $category;
        }

        return $urlPaths;
    }
}
