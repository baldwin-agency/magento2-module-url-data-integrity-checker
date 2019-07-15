<?php

declare(strict_types=1);

namespace Baldwin\UrlDataIntegrityChecker\Test\Checker\Catalog\Product;

use Baldwin\UrlDataIntegrityChecker\Checker\Catalog\Product\UrlKey as UrlKeyChecker;
use Baldwin\UrlDataIntegrityChecker\Console\Progress;
use Baldwin\UrlDataIntegrityChecker\Util\Stores as StoresUtil;
use Magento\Catalog\Model\Attribute\ScopeOverriddenValueFactory as AttributeScopeOverriddenValueFactory;
use Magento\Catalog\Model\ResourceModel\Product\CollectionFactory as ProductCollectionFactory;
use PHPUnit\Framework\TestCase;

class UrlKeyTest extends TestCase
{
    /**
     * @dataProvider duplicatedUrlKeyValuesDataProvider
     */
    public function testDuplicatedUrlKeyValues($dbData, $inheritedData, $skuToProductIdMapping, $expectedResults)
    {
        /** @var StoresUtil&PHPUnit_Framework_MockObject_MockObject */
        $storesUtilMock = $this
            ->getMockBuilder(StoresUtil::class)
            ->disableOriginalConstructor()
            ->getMock();

        /** @var Progress&PHPUnit_Framework_MockObject_MockObject */
        $progressMock = $this
            ->getMockBuilder(Progress::class)
            ->disableOriginalConstructor()
            ->getMock();

        /** @var ProductCollectionFactory&PHPUnit_Framework_MockObject_MockObject */
        $productCollectionFactoryMock = $this
            ->getMockBuilder(ProductCollectionFactory::class)
            ->disableOriginalConstructor()
            ->getMock();

        /** @var AttributeScopeOverriddenValueFactory&PHPUnit_Framework_MockObject_MockObject */
        $attributeScopeOverriddenValueFactoryMock = $this
            ->getMockBuilder(AttributeScopeOverriddenValueFactory::class)
            ->disableOriginalConstructor()
            ->getMock();

        $urlKeyChecker = new UrlKeyChecker(
            $storesUtilMock,
            $progressMock,
            $productCollectionFactoryMock,
            $attributeScopeOverriddenValueFactoryMock
        );

        $productUrlKeyDataProperty = (new \ReflectionClass($urlKeyChecker))->getProperty('cachedProductUrlKeyData');
        $productUrlKeyDataProperty->setAccessible(true);
        $productUrlKeyDataProperty->setValue($urlKeyChecker, $dbData);

        $inheritdProductUrlKeyDataProperty = (new \ReflectionClass($urlKeyChecker))
            ->getProperty('cachedInheritedProductUrlKeyData');
        $inheritdProductUrlKeyDataProperty->setAccessible(true);
        $inheritdProductUrlKeyDataProperty->setValue($urlKeyChecker, $inheritedData);

        $productSkusByIdsProperty = (new \ReflectionClass($urlKeyChecker))->getProperty('cachedProductSkusByIds');
        $productSkusByIdsProperty->setAccessible(true);
        $productSkusByIdsProperty->setValue($urlKeyChecker, $skuToProductIdMapping);

        $testMethod = new \ReflectionMethod(UrlKeyChecker::class, 'getProductsWithDuplicatedUrlKeyProblems');
        $testMethod->setAccessible(true);

        $result = $testMethod->invoke($urlKeyChecker);

        $this->assertEquals($expectedResults, $result);
    }

    public function duplicatedUrlKeyValuesDataProvider()
    {
        return [
            // 0. two products having different url key, is ok!
            [
                [
                    '0-1' => 'url_key_1',
                    '0-2' => 'url_key_2',
                ],
                [
                ],
                [
                    '1' => 'sku 1',
                    '2' => 'sku 2',
                ],
                [
                ],
            ],
            // 1. single product having different url key on multiple store views, is ok!
            [
                [
                    '0-1' => 'url_key_1',
                    '1-1' => 'url_key_2',
                ],
                [
                ],
                [
                    '1' => 'sku 1',
                ],
                [
                ],
            ],
            // 2. single product having the same url key on multiple store views, is ok!
            [
                [
                    '0-1' => 'url_key_1',
                    '1-1' => 'url_key_1',
                ],
                [
                ],
                [
                    '1' => 'sku 1',
                ],
                [
                ],
            ],
            // 3. two products having the same url key on the same store view, is not ok!
            [
                [
                    '0-1' => 'url_key_1',
                    '0-2' => 'url_key_1',
                ],
                [
                ],
                [
                    '1' => 'sku 1',
                    '2' => 'sku 2',
                ],
                [
                    [
                        'id'      => '1',
                        'sku'     => 'sku 1',
                        'storeId' => '0',
                        'problem' => sprintf(UrlKeyChecker::DUPLICATED_PROBLEM_DESCRIPTION, 2, 0),
                    ],
                    [
                        'id'      => '2',
                        'sku'     => 'sku 2',
                        'storeId' => '0',
                        'problem' => sprintf(UrlKeyChecker::DUPLICATED_PROBLEM_DESCRIPTION, 1, 0),
                    ],
                ],
            ],
            // 4. three products having the same url key on the same store view, is not ok!
            [
                [
                    '0-1' => 'url_key_1',
                    '0-2' => 'url_key_1',
                    '0-3' => 'url_key_1',
                ],
                [
                ],
                [
                    '1' => 'sku 1',
                    '2' => 'sku 2',
                    '3' => 'sku 3',
                ],
                [
                    [
                        'id'      => '1',
                        'sku'     => 'sku 1',
                        'storeId' => '0',
                        'problem' => sprintf(UrlKeyChecker::DUPLICATED_PROBLEM_DESCRIPTION, 2, 0),
                    ],
                    [
                        'id'      => '1',
                        'sku'     => 'sku 1',
                        'storeId' => '0',
                        'problem' => sprintf(UrlKeyChecker::DUPLICATED_PROBLEM_DESCRIPTION, 3, 0),
                    ],
                    [
                        'id'      => '2',
                        'sku'     => 'sku 2',
                        'storeId' => '0',
                        'problem' => sprintf(UrlKeyChecker::DUPLICATED_PROBLEM_DESCRIPTION, 1, 0),
                    ],
                    [
                        'id'      => '2',
                        'sku'     => 'sku 2',
                        'storeId' => '0',
                        'problem' => sprintf(UrlKeyChecker::DUPLICATED_PROBLEM_DESCRIPTION, 3, 0),
                    ],
                    [
                        'id'      => '3',
                        'sku'     => 'sku 3',
                        'storeId' => '0',
                        'problem' => sprintf(UrlKeyChecker::DUPLICATED_PROBLEM_DESCRIPTION, 1, 0),
                    ],
                    [
                        'id'      => '3',
                        'sku'     => 'sku 3',
                        'storeId' => '0',
                        'problem' => sprintf(UrlKeyChecker::DUPLICATED_PROBLEM_DESCRIPTION, 2, 0),
                    ],
                ],
            ],
            // 5. two products having the same url keys but on different store views, is ok!
            [
                [
                    '0-1' => 'url_key_1',
                    '1-1' => 'url_key_2',
                    '0-2' => 'url_key_2',
                    '1-2' => 'url_key_1',
                ],
                [
                ],
                [
                    '1' => 'sku 1',
                    '2' => 'sku 2',
                ],
                [
                ],
            ],
            // 6. two products having the same url keys where one inherits it from the default store view (0)
            //    and the other overwrites it on storeview level, is not ok!
            [
                [
                    '0-1' => 'url_key_1',
                    '0-2' => 'url_key_2',
                    '1-2' => 'url_key_1',
                ],
                [
                    '1-1' => 'url_key_1',
                ],
                [
                    '1' => 'sku 1',
                    '2' => 'sku 2',
                ],
                [
                    [
                        'id'      => '1',
                        'sku'     => 'sku 1',
                        'storeId' => '0',
                        'problem' => sprintf(UrlKeyChecker::DUPLICATED_PROBLEM_DESCRIPTION, 2, 1),
                    ],
                    [
                        'id'      => '2',
                        'sku'     => 'sku 2',
                        'storeId' => '1',
                        'problem' => sprintf(UrlKeyChecker::DUPLICATED_PROBLEM_DESCRIPTION, 1, 0),
                    ],
                ],
            ],
            // 7. two products where there is only a conflict on the default store view level (0)
            //    not on store view level 1, is not ok!
            [
                [
                    '0-1' => 'url_key_1',
                    '0-2' => 'url_key_1',
                    '1-1' => 'url_key_1',
                    '1-2' => 'url_key_2',
                ],
                [
                ],
                [
                    '1' => 'sku 1',
                    '2' => 'sku 2',
                ],
                [
                    [
                        'id'      => '1',
                        'sku'     => 'sku 1',
                        'storeId' => '0',
                        'problem' => sprintf(UrlKeyChecker::DUPLICATED_PROBLEM_DESCRIPTION, 2, 0),
                    ],
                    [
                        'id'      => '2',
                        'sku'     => 'sku 2',
                        'storeId' => '0',
                        'problem' => sprintf(UrlKeyChecker::DUPLICATED_PROBLEM_DESCRIPTION, 1, 0),
                    ],
                ],
            ],
            // 8. two products where there is a conflict on both store view levels, is ok!
            [
                [
                    '0-1' => 'url_key_1',
                    '0-2' => 'url_key_1',
                    '1-1' => 'url_key_1',
                    '1-2' => 'url_key_1',
                ],
                [
                ],
                [
                    '1' => 'sku 1',
                    '2' => 'sku 2',
                ],
                [
                    [
                        'id'      => '1',
                        'sku'     => 'sku 1',
                        'storeId' => '0',
                        'problem' => sprintf(UrlKeyChecker::DUPLICATED_PROBLEM_DESCRIPTION, 2, 0),
                    ],
                    [
                        'id'      => '2',
                        'sku'     => 'sku 2',
                        'storeId' => '0',
                        'problem' => sprintf(UrlKeyChecker::DUPLICATED_PROBLEM_DESCRIPTION, 1, 0),
                    ],
                    [
                        'id'      => '1',
                        'sku'     => 'sku 1',
                        'storeId' => '1',
                        'problem' => sprintf(UrlKeyChecker::DUPLICATED_PROBLEM_DESCRIPTION, 2, 1),
                    ],
                    [
                        'id'      => '2',
                        'sku'     => 'sku 2',
                        'storeId' => '1',
                        'problem' => sprintf(UrlKeyChecker::DUPLICATED_PROBLEM_DESCRIPTION, 1, 1),
                    ],
                ],
            ],
            // 9. two products where there is only a conflict on store view level 1, not on 0, is not ok!
            [
                [
                    '0-1' => 'url_key_1',
                    '0-2' => 'url_key_2',
                    '1-1' => 'url_key_2',
                    '1-2' => 'url_key_2',
                ],
                [
                ],
                [
                    '1' => 'sku 1',
                    '2' => 'sku 2',
                ],
                [
                    [
                        'id'      => '1',
                        'sku'     => 'sku 1',
                        'storeId' => '1',
                        'problem' => sprintf(UrlKeyChecker::DUPLICATED_PROBLEM_DESCRIPTION, 2, 1),
                    ],
                    [
                        'id'      => '2',
                        'sku'     => 'sku 2',
                        'storeId' => '1',
                        'problem' => sprintf(UrlKeyChecker::DUPLICATED_PROBLEM_DESCRIPTION, 1, 1),
                    ],
                ],
            ],
            // 10. two products without conflicts, is ok!
            [
                [
                    '0-1' => 'url_key_1',
                    '0-2' => 'url_key_2',
                    '1-1' => 'url_key_1',
                    '1-2' => 'url_key_2',
                ],
                [
                ],
                [
                    '1' => 'sku 1',
                    '2' => 'sku 2',
                ],
                [
                ],
            ],
            // 11. two products without conflicts, is ok!
            [
                [
                    '0-1' => 'url_key_1',
                    '0-2' => 'url_key_2',
                    '1-2' => 'url_key_2',
                ],
                [
                ],
                [
                    '1' => 'sku 1',
                    '2' => 'sku 2',
                ],
                [
                ],
            ],
            // 12. two products with conflicts, is not ok!
            [
                [
                    '0-1' => 'url_key_1',
                    '0-2' => 'url_key_1',
                    '1-2' => 'url_key_2',
                ],
                [
                    '1-1' => 'url_key_1',
                ],
                [
                    '1' => 'sku 1',
                    '2' => 'sku 2',
                ],
                [
                    [
                        'id'      => '1',
                        'sku'     => 'sku 1',
                        'storeId' => '0',
                        'problem' => sprintf(UrlKeyChecker::DUPLICATED_PROBLEM_DESCRIPTION, 2, 0),
                    ],
                    [
                        'id'      => '2',
                        'sku'     => 'sku 2',
                        'storeId' => '0',
                        'problem' => sprintf(UrlKeyChecker::DUPLICATED_PROBLEM_DESCRIPTION, 1, 0),
                    ],
                ],
            ],
            // 13. two products with conflicts, is not ok!
            [
                [
                    '0-1' => 'url_key_2',
                    '0-2' => 'url_key_1',
                    '1-1' => 'url_key_1',
                ],
                [
                    '1-2' => 'url_key_1',
                ],
                [
                    '1' => 'sku 1',
                    '2' => 'sku 2',
                ],
                [
                    [
                        'id'      => '2',
                        'sku'     => 'sku 2',
                        'storeId' => '0',
                        'problem' => sprintf(UrlKeyChecker::DUPLICATED_PROBLEM_DESCRIPTION, 1, 1),
                    ],
                    [
                        'id'      => '1',
                        'sku'     => 'sku 1',
                        'storeId' => '1',
                        'problem' => sprintf(UrlKeyChecker::DUPLICATED_PROBLEM_DESCRIPTION, 2, 0),
                    ],
                ],
            ],
            // 14. two products with all different url keys on different store views, is ok!
            [
                [
                    '0-1' => 'url_key_1',
                    '0-2' => 'url_key_2',
                    '1-1' => 'url_key_3',
                    '1-2' => 'url_key_1',
                ],
                [
                ],
                [
                    '1' => 'sku 1',
                    '2' => 'sku 2',
                ],
                [
                ],
            ],
        ];
    }
}
