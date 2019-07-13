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
use Symfony\Component\Console\Helper\Table as ConsoleTable;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class CheckProductUrlPaths extends ConsoleCommand
{
    const URL_PATH_ATTRIBUTE = 'url_path';
    const PROBLEM_DESCRIPTION =
          'Product has a non-null url_path attribute, this is known to cause problems with url rewrites in Magento.'
        . ' It\'s advised to remove this value from the database.';

    private $storesUtil;
    private $productCollectionFactory;
    private $attributeScopeOverriddenValueFactory;

    public function __construct(
        StoresUtil $storesUtil,
        ProductCollectionFactory $productCollectionFactory,
        AttributeScopeOverriddenValueFactory $attributeScopeOverriddenValueFactory
    ) {
        $this->storesUtil = $storesUtil;
        $this->productCollectionFactory = $productCollectionFactory;
        $this->attributeScopeOverriddenValueFactory = $attributeScopeOverriddenValueFactory;

        parent::__construct();
    }

    protected function configure()
    {
        $this->setName('catalog:product:integrity:urlpath');
        $this->setDescription('Checks data integrity of the values of the url_path product attribute.');

        parent::configure();
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        try {
            $this->checkForNonEmptyUrlPathAttributeValues($output);
        } catch (\Throwable $ex) {
            $output->writeln("<error>An unexpected exception occured: '{$ex->getMessage()}'</error>");
        }
    }

    private function checkForNonEmptyUrlPathAttributeValues(OutputInterface $output): void
    {
        $productsWithProblems = [];

        $storeIds = $this->storesUtil->getAllStoreIds();
        foreach ($storeIds as $storeId) {
            // we need a left join when using the non-default store view
            // and especially for the case where storeId 0 doesn't have a value set for this attribute
            $joinType = $storeId === Store::DEFAULT_STORE_ID ? 'inner' : 'left';

            $collection = $this->productCollectionFactory->create();
            $collection
                ->addStoreFilter($storeId)
                ->addAttributeToSelect(self::URL_PATH_ATTRIBUTE)
                ->addAttributeToFilter(self::URL_PATH_ATTRIBUTE, ['notnull' => true], $joinType)
            ;

            $productsWithProblems[] = $this->getProductsWithProblems($storeId, $collection);
        }

        $productsWithProblems = array_merge(...$productsWithProblems);

        $this->outputProblems($productsWithProblems, $output);
    }

    private function getProductsWithProblems(int $storeId, ProductCollection $collection): array
    {
        $products = [];

        foreach ($collection as $product) {
            $isOverridden = $this
                ->attributeScopeOverriddenValueFactory
                ->create()
                ->containsValue(ProductInterface::class, $product, self::URL_PATH_ATTRIBUTE, $storeId)
            ;

            if ($isOverridden || $storeId === Store::DEFAULT_STORE_ID) {
                $products[] = [
                    'id'      => $product->getEntityId(),
                    'sku'     => $product->getSku(),
                    'storeId' => $storeId,
                    'problem' => self::PROBLEM_DESCRIPTION,
                ];
            }
        }

        return $products;
    }

    private function outputProblems(array $productData, OutputInterface $output)
    {
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
