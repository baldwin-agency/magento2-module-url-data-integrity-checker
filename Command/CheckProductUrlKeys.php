<?php

declare(strict_types=1);

namespace Baldwin\UrlDataIntegrityChecker\Command;

use Baldwin\UrlDataIntegrityChecker\Checker\Catalog\Product\UrlKey as UrlKeyChecker;
use Baldwin\UrlDataIntegrityChecker\Console\Progress;
use Magento\Framework\App\Area as AppArea;
use Magento\Framework\App\State as AppState;
use Symfony\Component\Console\Command\Command as ConsoleCommand;
use Symfony\Component\Console\Helper\Table as ConsoleTable;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class CheckProductUrlKeys extends ConsoleCommand
{
    private $appState;
    private $progress;
    private $urlKeyChecker;

    public function __construct(
        AppState $appState,
        Progress $progress,
        UrlKeyChecker $urlKeyChecker
    ) {
        $this->appState = $appState;
        $this->progress = $progress;
        $this->urlKeyChecker = $urlKeyChecker;

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
            $this->progress->setOutput($output);

            $productData = $this->urlKeyChecker->execute();
            $this->outputProblems($productData, $output);
        } catch (\Throwable $ex) {
            $output->writeln("<error>An unexpected exception occured: '{$ex->getMessage()}'</error>");
        }
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
