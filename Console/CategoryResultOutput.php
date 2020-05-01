<?php

declare(strict_types=1);

namespace Baldwin\UrlDataIntegrityChecker\Console;

use Magento\Framework\Console\Cli;
use Symfony\Component\Console\Helper\Table as ConsoleTable;
use Symfony\Component\Console\Output\OutputInterface;

class CategoryResultOutput
{
    /**
     * @param array<array<string, mixed>> $categoryData
     */
    public function outputResult(array $categoryData, OutputInterface $output): int
    {
        if (empty($categoryData)) {
            $output->writeln('<info>No problems found!</info>');

            return Cli::RETURN_SUCCESS;
        }

        // sort by catId and storeId
        usort($categoryData, function ($catA, $catB) {
            if ($catA['catId'] === $catB['catId']) {
                return $catA['storeId'] <=> $catB['storeId'];
            }

            return $catA['catId'] <=> $catB['catId'];
        });

        $table = new ConsoleTable($output);
        $table->setHeaders(['Category ID', 'Name', 'Store ID', 'Problem']);
        $table->setRows($categoryData);

        $table->render();

        return Cli::RETURN_FAILURE;
    }
}
