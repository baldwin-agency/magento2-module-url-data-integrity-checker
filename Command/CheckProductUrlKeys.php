<?php

declare(strict_types=1);

namespace Baldwin\UrlDataIntegrityChecker\Command;

use Baldwin\UrlDataIntegrityChecker\Util\Stores as StoresUtil;
use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Catalog\Model\Attribute\ScopeOverriddenValueFactory as AttributeScopeOverriddenValueFactory;
use Magento\Catalog\Model\ResourceModel\Product\Collection as ProductCollection;
use Magento\Catalog\Model\ResourceModel\Product\CollectionFactory as ProductCollectionFactory;
use Magento\Framework\App\Area as AppArea;
use Magento\Framework\App\State as AppState;
use Magento\Store\Model\Store;
use Symfony\Component\Console\Command\Command as ConsoleCommand;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Helper\Table as ConsoleTable;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class CheckProductUrlKeys extends ConsoleCommand
{
    const URL_KEY_ATTRIBUTE = 'url_key';
    const EMPTY_PROBLEM_DESCRIPTION = 'Product has an empty url_key value. This needs to be fixed.';
    const DUPLICATED_PROBLEM_DESCRIPTION =
        'Product has a duplicated url_key value. It\'s the same as another product (ID: %s, Store: %s)';

    private $storesUtil;
    private $productCollectionFactory;
    private $attributeScopeOverriddenValueFactory;
    private $appState;

    private $cachedProductUrlKeyData;

    public function __construct(
        StoresUtil $storesUtil,
        ProductCollectionFactory $productCollectionFactory,
        AttributeScopeOverriddenValueFactory $attributeScopeOverriddenValueFactory,
        AppState $appState
    ) {
        $this->storesUtil = $storesUtil;
        $this->productCollectionFactory = $productCollectionFactory;
        $this->attributeScopeOverriddenValueFactory = $attributeScopeOverriddenValueFactory;
        $this->appState = $appState;

        $this->cachedProductUrlKeyData = [];

        parent::__construct();
    }

    protected function configure()
    {
        $this->setName('catalog:product:integrity:urlkey');
        $this->setDescription('Checks data integrity of the values of the url_key product attribute.');

        parent::configure();
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        try {
            $this->appState->setAreaCode(AppArea::AREA_CRONTAB);

            $this->checkForEmptyUrlKeyAttributeValues($output);
            $this->checkForDuplicatedUrlKeyAttributeValues($output);
        } catch (\Throwable $ex) {
            $output->writeln("<error>An unexpected exception occured: '{$ex->getMessage()}'</error>");
        }
    }

    private function checkForEmptyUrlKeyAttributeValues(OutputInterface $output): void
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

        $this->outputProblems($productsWithProblems, $output);
    }

    private function checkForDuplicatedUrlKeyAttributeValues(OutputInterface $output): void
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

            $this->storeProductUrlKeyData($output, $storeId, $collection);
        }

        $productsWithProblems = $this->getProductsWithDuplicatedUrlKeyProblems();

        $this->outputProblems($productsWithProblems, $output);
    }

    private function storeProductUrlKeyData(OutputInterface $output, int $storeId, ProductCollection $collection): void
    {
        $progress = new ProgressBar($output, $collection->getSize());
        $progress->setRedrawFrequency(50);
        $progress->setFormat(" %message%\n %current%/%max% %bar% %percent%%\n");
        $progress->setMessage("Fetching data for store $storeId");
        $progress->start();

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

            $progress->advance();
        }

        $progress->setMessage("Finished fetching data for store $storeId");
        $progress->finish();
    }

    private function getProductsWithDuplicatedUrlKeyProblems(): array
    {
        $products = [];

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
        );

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
                            'sku'     => 'TODO',
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
                        // TODO: this is pretty complex and I'm not sure if this was implemented 100% correct,
                        // need to review and also document properly
                        $conflictingDataKey = $storeId . '-' . $conflictingProductId;
                        if ($storeId !== Store::DEFAULT_STORE_ID
                            && !array_key_exists($conflictingDataKey, $potentialDuplicatedUrlKeys)) {
                            $products[] = [
                                'id'      => $productId,
                                'sku'     => 'TODO',
                                'storeId' => $storeId,
                                'problem' => sprintf(
                                    self::DUPLICATED_PROBLEM_DESCRIPTION,
                                    $conflictingProductId,
                                    $conflictingStoreId
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

    private function outputProblems(array $productData, OutputInterface $output): void
    {
        if (empty($productData)) {
            $output->writeln('<info>No problems found!</info>');

            return;
        }

        usort($productData, function ($prodA, $prodB) {
            if ($prodA['id'] === $prodB['id']) {
                return $prodA['storeId'] <=> $prodB['storeId'];
            }

            return $prodA['id'] <=> $prodB['id'];
        });

        $table = new ConsoleTable($output);
        $table->setHeaders(['ID', 'SKU', 'Store', 'Problem']);
        $table->setRows($productData);

        $table->render();
    }
}
