<?php

declare(strict_types=1);

namespace Baldwin\UrlDataIntegrityChecker\Console\Command;

use Baldwin\UrlDataIntegrityChecker\Console\ProductResultOutput;
use Baldwin\UrlDataIntegrityChecker\Console\Progress;
use Baldwin\UrlDataIntegrityChecker\Storage\Meta as MetaStorage;
use Baldwin\UrlDataIntegrityChecker\Updater\Catalog\Product\UrlKey as UrlKeyUpdater;
use Magento\Framework\App\Area as AppArea;
use Magento\Framework\App\State as AppState;
use Magento\Framework\Console\Cli;
use Symfony\Component\Console\Command\Command as ConsoleCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class CheckProductUrlKeys extends ConsoleCommand
{
    private $appState;
    private $progress;
    private $resultOutput;
    private $urlKeyUpdater;

    public function __construct(
        AppState $appState,
        Progress $progress,
        ProductResultOutput $resultOutput,
        UrlKeyUpdater $urlKeyUpdater
    ) {
        $this->appState = $appState;
        $this->progress = $progress;
        $this->resultOutput = $resultOutput;
        $this->urlKeyUpdater = $urlKeyUpdater;

        parent::__construct();
    }

    protected function configure(): void
    {
        $this->setName('catalog:product:integrity:urlkey');
        $this->setDescription('Checks data integrity of the values of the url_key product attribute.');
        $this->addOption(
            'force',
            'f',
            InputOption::VALUE_NONE,
            '[Deprecated] this option doesn\'t do anything anymore'
        );

        parent::configure();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        try {
            $this->appState->setAreaCode(AppArea::AREA_CRONTAB);
            $this->progress->setOutput($output);

            $productData = $this->urlKeyUpdater->refresh(MetaStorage::INITIATOR_CLI);
            $cliResult = $this->resultOutput->outputResult($productData, $output);

            $output->writeln(
                "\n<info>Data was stored and you can now also review it in the admin of Magento</info>"
            );

            return $cliResult;
        } catch (\Throwable $ex) {
            $output->writeln(
                "<error>An unexpected exception occured: '{$ex->getMessage()}'</error>\n{$ex->getTraceAsString()}"
            );
        }

        return Cli::RETURN_FAILURE;
    }
}
