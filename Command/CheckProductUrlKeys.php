<?php

declare(strict_types=1);

namespace Baldwin\UrlDataIntegrityChecker\Command;

use Baldwin\UrlDataIntegrityChecker\Util\Stores as StoresUtil;
use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Catalog\Model\Attribute\ScopeOverriddenValueFactory as AttributeScopeOverriddenValueFactory;
use Magento\Catalog\Model\ResourceModel\Product\Collection as ProductCollection;
use Magento\Catalog\Model\ResourceModel\Product\CollectionFactory as ProductCollectionFactory;
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
        'Product has a duplicated url_key value. It\'s the same as other products (ID\'s: %s)';

    private $storesUtil;
    private $productCollectionFactory;
    private $attributeScopeOverriddenValueFactory;

    private $cachedProductUrlKeyData;
    private $cachedProductUrlKeyCountByStore;

    public function __construct(
        StoresUtil $storesUtil,
        ProductCollectionFactory $productCollectionFactory,
        AttributeScopeOverriddenValueFactory $attributeScopeOverriddenValueFactory
    ) {
        $this->storesUtil = $storesUtil;
        $this->productCollectionFactory = $productCollectionFactory;
        $this->attributeScopeOverriddenValueFactory = $attributeScopeOverriddenValueFactory;

        $this->cachedProductUrlKeyData = [];
        $this->cachedProductUrlKeyCountByStore = [];

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
                // ->addAttributeToFilter('entity_id', ['in' => [147, 148, 149, 150, 151, 152, 153, 154, 155, 156, 157]]) // TODO: remove!
            ;

            $this->storeProductUrlKeyData($output, $storeId, $collection);
        }

        $productsWithProblems = $this->getProductsWithDuplicatedUrlKeyProblems();

        $this->outputProblems($productsWithProblems, $output);
    }

    private function storeProductUrlKeyData(OutputInterface $output, int $storeId, ProductCollection $collection): void
    {
        // TODO: in older symfony/console < 2.5 we should use the Progress Helper (https://symfony.com/doc/2.3/components/console/helpers/progresshelper.html) which was removed in >= 3.0
        $progress = new ProgressBar($output, $collection->getSize());
        $progress->setRedrawFrequency(50);
        $progress->setFormat(" %message%\n %current%/%max% %bar% %percent%%\n");
        $progress->setMessage("Fetching data for store $storeId");
        $progress->start();

        $this->cachedProductUrlKeyData[$storeId] = [];
        $this->cachedProductUrlKeyCountByStore[$storeId] = [];

        foreach ($collection as $product)
        {
            $productId     = $product->getEntityId();
            $productSku    = $product->getSku();
            $productUrlKey = $product->getUrlKey();

            $isOverridden = $this
                ->attributeScopeOverriddenValueFactory
                ->create()
                ->containsValue(ProductInterface::class, $product, self::URL_KEY_ATTRIBUTE, $storeId)
            ;
            if ($isOverridden || $storeId === Store::DEFAULT_STORE_ID) {

                $this->cachedProductUrlKeyData[$storeId][$productId] = [
                    'id'      => $productId,
                    'sku'     => $productSku,
                    'url_key' => $productUrlKey,
                ];

                if (!array_key_exists($productUrlKey, $this->cachedProductUrlKeyCountByStore[$storeId])) {
                    $this->cachedProductUrlKeyCountByStore[$storeId][$productUrlKey] = [];
                }

                $this->cachedProductUrlKeyCountByStore[$storeId][$productUrlKey][] = $productId;
            }

            $progress->advance();
        }

        $progress->setMessage("Finished fetching data for store $storeId");
        $progress->finish();
    }

    private function getProductsWithDuplicatedUrlKeyProblems(): array
    {
        $products = [];

        // print_r($this->cachedProductUrlKeyData);
        // print_r($this->cachedProductUrlKeyCountByStore);

        foreach ($this->cachedProductUrlKeyCountByStore as $storeId => $urlKeyCountData) {
            foreach ($urlKeyCountData as $urlKey => $prodIds) {
                if (count($prodIds) > 1) {
                    foreach ($prodIds as $prodId) {
                        $productData = $this->cachedProductUrlKeyData[$storeId][$prodId];

                        $products[] = [
                            'id'      => $productData['id'],
                            'sku'     => $productData['sku'],
                            'storeId' => $storeId,
                            'problem' => sprintf(self::DUPLICATED_PROBLEM_DESCRIPTION, implode(', ', array_diff($prodIds, [$prodId]))),
                        ];
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
