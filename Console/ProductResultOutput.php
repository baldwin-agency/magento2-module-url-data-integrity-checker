<?php

declare(strict_types=1);

namespace Baldwin\UrlDataIntegrityChecker\Console;

use Magento\Framework\Console\Cli;
use Symfony\Component\Console\Helper\Table as ConsoleTable;
use Symfony\Component\Console\Output\OutputInterface;

class ProductResultOutput
{
    /**
     * @param array<array<string, mixed>> $productData
     */
    public function outputResult(array $productData, OutputInterface $output): int
    {
        if (empty($productData)) {
            $output->writeln('<info>No problems found!</info>');

            return Cli::RETURN_SUCCESS;
        }

        // sort by productId and storeId
        usort($productData, function ($prodA, $prodB) {
            if ($prodA['productId'] === $prodB['productId']) {
                return $prodA['storeId'] <=> $prodB['storeId'];
            }

            return $prodA['productId'] <=> $prodB['productId'];
        });

        $table = new ConsoleTable($output);
        $table->setHeaders(['Product ID', 'SKU', 'Store ID', 'Problem']);
        $table->setRows($productData);

        $table->render();

        return Cli::RETURN_FAILURE;
    }
}
