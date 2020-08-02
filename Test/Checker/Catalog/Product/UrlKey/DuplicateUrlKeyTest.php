<?php

declare(strict_types=1);

namespace Baldwin\UrlDataIntegrityChecker\Test\Checker\Catalog\Product\UrlKey;

use Baldwin\UrlDataIntegrityChecker\Checker\Catalog\Product\UrlKey\DuplicateUrlKey as UrlKeyChecker;
use Baldwin\UrlDataIntegrityChecker\Console\Progress;
use Baldwin\UrlDataIntegrityChecker\Util\Stores as StoresUtil;
use Magento\Catalog\Model\Attribute\ScopeOverriddenValue as AttributeScopeOverriddenValue;
use Magento\Catalog\Model\Attribute\ScopeOverriddenValueFactory as AttributeScopeOverriddenValueFactory;
use Magento\Catalog\Model\ResourceModel\Product\Collection as ProductCollection;
use Magento\Catalog\Model\ResourceModel\Product\CollectionFactory as ProductCollectionFactory;
use Magento\Framework\DataObject;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager as ObjectManagerHelper;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class DuplicateUrlKeyTest extends TestCase
{
    /** @var ObjectManagerHelper */
    private $objectManagerHelper;

    protected function setUp(): void
    {
        $this->objectManagerHelper = new ObjectManagerHelper($this);
    }

    /**
     * @dataProvider duplicatedProductUrlKeyValuesDataProvider
     *
     * @param array<array<string, mixed>> $dbData
     * @param array<array<string, mixed>> $expectedResults
     */
    public function testDuplicatedProductUrlKeyValues(array $dbData, array $expectedResults)
    {
        $dbData = array_map(function ($productData) {
            return new DataObject($productData);
        }, $dbData);

        $storeIds = array_unique(
            array_map(function ($productData) {
                return $productData->getStoreId();
            }, $dbData)
        );

        $dataPerStoreId = [];
        foreach ($storeIds as $storeId) {
            $dataPerStoreId[] = array_filter(
                $dbData,
                function ($productData) use ($storeId) {
                    return $productData->getStoreId() === $storeId;
                }
            );
        }

        $collectionsPerStoreId = array_map(
            function ($productsData) {
                /** @var MockObject $productCollectionMock */
                $productCollectionMock = $this->objectManagerHelper
                    ->getCollectionMock(ProductCollection::class, $productsData);

                $productCollectionMock->expects($this->once())
                    ->method('setStoreId')
                    ->willReturnSelf();
                $productCollectionMock->expects($this->once())
                    ->method('addAttributeToSelect')
                    ->willReturnSelf();
                $productCollectionMock->expects($this->exactly(2))
                    ->method('addAttributeToFilter')
                    ->willReturnSelf();
                $productCollectionMock->expects($this->atLeastOnce())
                    ->method('getSize')
                    ->willReturn(count($productsData));

                return $productCollectionMock;
            },
            $dataPerStoreId
        );

        /** @var StoresUtil&MockObject */
        $storesUtilMock = $this
            ->getMockBuilder(StoresUtil::class)
            ->disableOriginalConstructor()
            ->getMock();
        $storesUtilMock->expects($this->exactly(2))
            ->method('getAllStoreIds')
            ->willReturn($storeIds);

        /** @var Progress&MockObject */
        $progressMock = $this
            ->getMockBuilder(Progress::class)
            ->disableOriginalConstructor()
            ->getMock();

        /** @var ProductCollectionFactory&MockObject */
        $productCollectionFactoryMock = $this
            ->getMockBuilder(ProductCollectionFactory::class)
            ->disableOriginalConstructor()
            ->setMethods(['create'])
            ->getMock();

        $productCollectionFactoryMock->expects($this->exactly(count($storeIds)))
            ->method('create')
            ->will($this->onConsecutiveCalls(...$collectionsPerStoreId)); // ... turns array into seperate arguments

        $attributeScopeOverriddenValueMock = $this
            ->getMockBuilder(AttributeScopeOverriddenValue::class)
            ->disableOriginalConstructor()
            ->getMock();

        // TODO: this isn't strictly correct, but always returning true seems to work for our test cases !!!!
        $attributeScopeOverriddenValueMock->expects($this->exactly(count($dbData)))
            ->method('containsValue')
            ->willReturn(true);

        /** @var AttributeScopeOverriddenValueFactory&MockObject */
        $attributeScopeOverriddenValueFactoryMock = $this
            ->getMockBuilder(AttributeScopeOverriddenValueFactory::class)
            ->disableOriginalConstructor()
            ->setMethods(['create'])
            ->getMock();
        $attributeScopeOverriddenValueFactoryMock->expects($this->exactly(count($dbData)))
            ->method('create')
            ->willReturn($attributeScopeOverriddenValueMock);

        $urlKeyChecker = new UrlKeyChecker(
            $storesUtilMock,
            $progressMock,
            $productCollectionFactoryMock,
            $attributeScopeOverriddenValueFactoryMock
        );
        $results = $urlKeyChecker->execute();

        $this->assertEquals($expectedResults, $results);
    }

    /**
     * @return array<array<array<array<string, mixed>>>>
     */
    public function duplicatedProductUrlKeyValuesDataProvider(): array
    {
        return [
            // 0. two products having different url key, is ok!
            [
                [
                    [
                        'entity_id'  => 1,
                        'sku'        => 'sku 1',
                        'store_id'   => 0,
                        'url_key'    => 'url_key_1',
                    ],
                    [
                        'entity_id'  => 2,
                        'sku'        => 'sku 2',
                        'store_id'   => 0,
                        'url_key'    => 'url_key_2',
                    ],
                ],
                [
                ],
            ],
            // // 1. single product having different url key on multiple store views, is ok!
            [
                [
                    [
                        'entity_id'  => 1,
                        'sku'        => 'sku 1',
                        'store_id'   => 0,
                        'url_key'    => 'url_key_1',
                    ],
                    [
                        'entity_id'  => 1,
                        'sku'        => 'sku 1',
                        'store_id'   => 1,
                        'url_key'    => 'url_key_2',
                    ],
                ],
                [
                ],
            ],
            // // 2. single product having the same url key on multiple store views, is ok!
            [
                [
                    [
                        'entity_id'  => 1,
                        'sku'        => 'sku 1',
                        'store_id'   => 0,
                        'url_key'    => 'url_key_1',
                    ],
                    [
                        'entity_id'  => 1,
                        'sku'        => 'sku 1',
                        'store_id'   => 1,
                        'url_key'    => 'url_key_1',
                    ],
                ],
                [
                ],
            ],
            // 3. two products having the same url key on the same store view, is not ok!
            [
                [
                    [
                        'entity_id'  => 1,
                        'sku'        => 'sku 1',
                        'store_id'   => 0,
                        'url_key'    => 'url_key_1',
                    ],
                    [
                        'entity_id'  => 2,
                        'sku'        => 'sku 2',
                        'store_id'   => 0,
                        'url_key'    => 'url_key_1',
                    ],
                ],
                [
                    [
                        'productId' => 1,
                        'sku'       => 'sku 1',
                        'storeId'   => 0,
                        'problem'   => sprintf(UrlKeyChecker::DUPLICATED_PROBLEM_DESCRIPTION, 'url_key_1', 2, 0),
                    ],
                    [
                        'productId' => 2,
                        'sku'       => 'sku 2',
                        'storeId'   => 0,
                        'problem'   => sprintf(UrlKeyChecker::DUPLICATED_PROBLEM_DESCRIPTION, 'url_key_1', 1, 0),
                    ],
                ],
            ],
            // 4. three products having the same url key on the same store view, is not ok!
            [
                [
                    [
                        'entity_id'  => 1,
                        'sku'        => 'sku 1',
                        'store_id'   => 0,
                        'url_key'    => 'url_key_1',
                    ],
                    [
                        'entity_id'  => 2,
                        'sku'        => 'sku 2',
                        'store_id'   => 0,
                        'url_key'    => 'url_key_1',
                    ],
                    [
                        'entity_id'  => 3,
                        'sku'        => 'sku 3',
                        'store_id'   => 0,
                        'url_key'    => 'url_key_1',
                    ],
                ],
                [
                    [
                        'productId' => '1',
                        'sku'       => 'sku 1',
                        'storeId'   => '0',
                        'problem'   => sprintf(UrlKeyChecker::DUPLICATED_PROBLEM_DESCRIPTION, 'url_key_1', 2, 0),
                    ],
                    [
                        'productId' => '1',
                        'sku'       => 'sku 1',
                        'storeId'   => '0',
                        'problem'   => sprintf(UrlKeyChecker::DUPLICATED_PROBLEM_DESCRIPTION, 'url_key_1', 3, 0),
                    ],
                    [
                        'productId' => '2',
                        'sku'       => 'sku 2',
                        'storeId'   => '0',
                        'problem'   => sprintf(UrlKeyChecker::DUPLICATED_PROBLEM_DESCRIPTION, 'url_key_1', 1, 0),
                    ],
                    [
                        'productId' => '2',
                        'sku'       => 'sku 2',
                        'storeId'   => '0',
                        'problem'   => sprintf(UrlKeyChecker::DUPLICATED_PROBLEM_DESCRIPTION, 'url_key_1', 3, 0),
                    ],
                    [
                        'productId' => '3',
                        'sku'       => 'sku 3',
                        'storeId'   => '0',
                        'problem'   => sprintf(UrlKeyChecker::DUPLICATED_PROBLEM_DESCRIPTION, 'url_key_1', 1, 0),
                    ],
                    [
                        'productId' => '3',
                        'sku'       => 'sku 3',
                        'storeId'   => '0',
                        'problem'   => sprintf(UrlKeyChecker::DUPLICATED_PROBLEM_DESCRIPTION, 'url_key_1', 2, 0),
                    ],
                ],
            ],
            // 5. two products having the same url keys but on different store views, is ok!
            [
                [
                    [
                        'entity_id'  => 1,
                        'sku'        => 'sku 1',
                        'store_id'   => 0,
                        'url_key'    => 'url_key_1',
                    ],
                    [
                        'entity_id'  => 1,
                        'sku'        => 'sku 1',
                        'store_id'   => 1,
                        'url_key'    => 'url_key_2',
                    ],
                    [
                        'entity_id'  => 2,
                        'sku'        => 'sku 2',
                        'store_id'   => 0,
                        'url_key'    => 'url_key_2',
                    ],
                    [
                        'entity_id'  => 2,
                        'sku'        => 'sku 2',
                        'store_id'   => 1,
                        'url_key'    => 'url_key_1',
                    ],
                ],
                [
                ],
            ],
            // 6. two products having the same url keys where one inherits it from the default store view (0)
            //    and the other overwrites it on storeview level, is not ok!
            [
                [
                    [
                        'entity_id'  => 1,
                        'sku'        => 'sku 1',
                        'store_id'   => 0,
                        'url_key'    => 'url_key_1',
                    ],
                    [
                        'entity_id'  => 2,
                        'sku'        => 'sku 2',
                        'store_id'   => 0,
                        'url_key'    => 'url_key_2',
                    ],
                    [
                        'entity_id'  => 2,
                        'sku'        => 'sku 2',
                        'store_id'   => 1,
                        'url_key'    => 'url_key_1',
                    ],
                ],
                [
                    [
                        'productId' => '1',
                        'sku'       => 'sku 1',
                        'storeId'   => '0',
                        'problem'   => sprintf(UrlKeyChecker::DUPLICATED_PROBLEM_DESCRIPTION, 'url_key_1', 2, 1),
                    ],
                    [
                        'productId' => '2',
                        'sku'       => 'sku 2',
                        'storeId'   => '1',
                        'problem'   => sprintf(UrlKeyChecker::DUPLICATED_PROBLEM_DESCRIPTION, 'url_key_1', 1, 0),
                    ],
                ],
            ],
            // 7. two products where there is only a conflict on the default store view level (0)
            //    not on store view level 1, is not ok!
            [
                [
                    [
                        'entity_id'  => 1,
                        'sku'        => 'sku 1',
                        'store_id'   => 0,
                        'url_key'    => 'url_key_1',
                    ],
                    [
                        'entity_id'  => 2,
                        'sku'        => 'sku 2',
                        'store_id'   => 0,
                        'url_key'    => 'url_key_1',
                    ],
                    [
                        'entity_id'  => 1,
                        'sku'        => 'sku 1',
                        'store_id'   => 1,
                        'url_key'    => 'url_key_1',
                    ],
                    [
                        'entity_id'  => 2,
                        'sku'        => 'sku 2',
                        'store_id'   => 1,
                        'url_key'    => 'url_key_2',
                    ],
                ],
                [
                    [
                        'productId' => '1',
                        'sku'       => 'sku 1',
                        'storeId'   => '0',
                        'problem'   => sprintf(UrlKeyChecker::DUPLICATED_PROBLEM_DESCRIPTION, 'url_key_1', 2, 0),
                    ],
                    [
                        'productId' => '2',
                        'sku'       => 'sku 2',
                        'storeId'   => '0',
                        'problem'   => sprintf(UrlKeyChecker::DUPLICATED_PROBLEM_DESCRIPTION, 'url_key_1', 1, 0),
                    ],
                ],
            ],
            // 8. two products where there is a conflict on both store view levels, is ok!
            [
                [
                    [
                        'entity_id'  => 1,
                        'sku'        => 'sku 1',
                        'store_id'   => 0,
                        'url_key'    => 'url_key_1',
                    ],
                    [
                        'entity_id'  => 2,
                        'sku'        => 'sku 2',
                        'store_id'   => 0,
                        'url_key'    => 'url_key_1',
                    ],
                    [
                        'entity_id'  => 1,
                        'sku'        => 'sku 1',
                        'store_id'   => 1,
                        'url_key'    => 'url_key_1',
                    ],
                    [
                        'entity_id'  => 2,
                        'sku'        => 'sku 2',
                        'store_id'   => 1,
                        'url_key'    => 'url_key_1',
                    ],
                ],
                [
                    [
                        'productId' => '1',
                        'sku'       => 'sku 1',
                        'storeId'   => '0',
                        'problem'   => sprintf(UrlKeyChecker::DUPLICATED_PROBLEM_DESCRIPTION, 'url_key_1', 2, 0),
                    ],
                    [
                        'productId' => '2',
                        'sku'       => 'sku 2',
                        'storeId'   => '0',
                        'problem'   => sprintf(UrlKeyChecker::DUPLICATED_PROBLEM_DESCRIPTION, 'url_key_1', 1, 0),
                    ],
                    [
                        'productId' => '1',
                        'sku'       => 'sku 1',
                        'storeId'   => '1',
                        'problem'   => sprintf(UrlKeyChecker::DUPLICATED_PROBLEM_DESCRIPTION, 'url_key_1', 2, 1),
                    ],
                    [
                        'productId' => '2',
                        'sku'       => 'sku 2',
                        'storeId'   => '1',
                        'problem'   => sprintf(UrlKeyChecker::DUPLICATED_PROBLEM_DESCRIPTION, 'url_key_1', 1, 1),
                    ],
                ],
            ],
            // 9. two products where there is only a conflict on store view level 1, not on 0, is not ok!
            [
                [
                    [
                        'entity_id'  => 1,
                        'sku'        => 'sku 1',
                        'store_id'   => 0,
                        'url_key'    => 'url_key_1',
                    ],
                    [
                        'entity_id'  => 2,
                        'sku'        => 'sku 2',
                        'store_id'   => 0,
                        'url_key'    => 'url_key_2',
                    ],
                    [
                        'entity_id'  => 1,
                        'sku'        => 'sku 1',
                        'store_id'   => 1,
                        'url_key'    => 'url_key_2',
                    ],
                    [
                        'entity_id'  => 2,
                        'sku'        => 'sku 2',
                        'store_id'   => 1,
                        'url_key'    => 'url_key_2',
                    ],
                ],
                [
                    [
                        'productId' => '1',
                        'sku'       => 'sku 1',
                        'storeId'   => '1',
                        'problem'   => sprintf(UrlKeyChecker::DUPLICATED_PROBLEM_DESCRIPTION, 'url_key_2', 2, 1),
                    ],
                    [
                        'productId' => '2',
                        'sku'       => 'sku 2',
                        'storeId'   => '1',
                        'problem'   => sprintf(UrlKeyChecker::DUPLICATED_PROBLEM_DESCRIPTION, 'url_key_2', 1, 1),
                    ],
                ],
            ],
            // 10. two products without conflicts, is ok!
            [
                [
                    [
                        'entity_id'  => 1,
                        'sku'        => 'sku 1',
                        'store_id'   => 0,
                        'url_key'    => 'url_key_1',
                    ],
                    [
                        'entity_id'  => 2,
                        'sku'        => 'sku 2',
                        'store_id'   => 0,
                        'url_key'    => 'url_key_2',
                    ],
                    [
                        'entity_id'  => 1,
                        'sku'        => 'sku 1',
                        'store_id'   => 1,
                        'url_key'    => 'url_key_1',
                    ],
                    [
                        'entity_id'  => 2,
                        'sku'        => 'sku 2',
                        'store_id'   => 1,
                        'url_key'    => 'url_key_2',
                    ],
                ],
                [
                ],
            ],
            // 11. two products without conflicts, is ok!
            [
                [
                    [
                        'entity_id'  => 1,
                        'sku'        => 'sku 1',
                        'store_id'   => 0,
                        'url_key'    => 'url_key_1',
                    ],
                    [
                        'entity_id'  => 2,
                        'sku'        => 'sku 2',
                        'store_id'   => 0,
                        'url_key'    => 'url_key_2',
                    ],
                    [
                        'entity_id'  => 2,
                        'sku'        => 'sku 2',
                        'store_id'   => 1,
                        'url_key'    => 'url_key_2',
                    ],
                ],
                [
                ],
            ],
            // 12. two products with conflicts, is not ok!
            [
                [
                    [
                        'entity_id'  => 1,
                        'sku'        => 'sku 1',
                        'store_id'   => 0,
                        'url_key'    => 'url_key_1',
                    ],
                    [
                        'entity_id'  => 2,
                        'sku'        => 'sku 2',
                        'store_id'   => 0,
                        'url_key'    => 'url_key_1',
                    ],
                    [
                        'entity_id'  => 2,
                        'sku'        => 'sku 2',
                        'store_id'   => 1,
                        'url_key'    => 'url_key_2',
                    ],
                ],
                [
                    [
                        'productId' => '1',
                        'sku'       => 'sku 1',
                        'storeId'   => '0',
                        'problem'   => sprintf(UrlKeyChecker::DUPLICATED_PROBLEM_DESCRIPTION, 'url_key_1', 2, 0),
                    ],
                    [
                        'productId' => '2',
                        'sku'       => 'sku 2',
                        'storeId'   => '0',
                        'problem'   => sprintf(UrlKeyChecker::DUPLICATED_PROBLEM_DESCRIPTION, 'url_key_1', 1, 0),
                    ],
                ],
            ],
            // 13. two products with conflicts, is not ok!
            [
                [
                    [
                        'entity_id'  => 1,
                        'sku'        => 'sku 1',
                        'store_id'   => 0,
                        'url_key'    => 'url_key_2',
                    ],
                    [
                        'entity_id'  => 2,
                        'sku'        => 'sku 2',
                        'store_id'   => 0,
                        'url_key'    => 'url_key_1',
                    ],
                    [
                        'entity_id'  => 1,
                        'sku'        => 'sku 1',
                        'store_id'   => 1,
                        'url_key'    => 'url_key_1',
                    ],
                ],
                [
                    [
                        'productId' => '2',
                        'sku'       => 'sku 2',
                        'storeId'   => '0',
                        'problem'   => sprintf(UrlKeyChecker::DUPLICATED_PROBLEM_DESCRIPTION, 'url_key_1', 1, 1),
                    ],
                    [
                        'productId' => '1',
                        'sku'       => 'sku 1',
                        'storeId'   => '1',
                        'problem'   => sprintf(UrlKeyChecker::DUPLICATED_PROBLEM_DESCRIPTION, 'url_key_1', 2, 0),
                    ],
                ],
            ],
            // 14. two products with all different url keys on different store views, is ok!
            [
                [
                    [
                        'entity_id'  => 1,
                        'sku'        => 'sku 1',
                        'store_id'   => 0,
                        'url_key'    => 'url_key_1',
                    ],
                    [
                        'entity_id'  => 2,
                        'sku'        => 'sku 2',
                        'store_id'   => 0,
                        'url_key'    => 'url_key_2',
                    ],
                    [
                        'entity_id'  => 1,
                        'sku'        => 'sku 1',
                        'store_id'   => 1,
                        'url_key'    => 'url_key_3',
                    ],
                    [
                        'entity_id'  => 2,
                        'sku'        => 'sku 2',
                        'store_id'   => 1,
                        'url_key'    => 'url_key_1',
                    ],
                ],
                [
                ],
            ],
        ];
    }
}
