<?php

declare(strict_types=1);

namespace Baldwin\UrlDataIntegrityChecker\Console\Command;

use Baldwin\UrlDataIntegrityChecker\Checker\Catalog\Product\UrlKey as UrlKeyChecker;
use Baldwin\UrlDataIntegrityChecker\Console\Progress;
use Baldwin\UrlDataIntegrityChecker\Console\ResultOutput;
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
    private $metaStorage;

    public function __construct(
        AppState $appState,
        Progress $progress,
        ResultOutput $resultOutput,
        UrlKeyUpdater $urlKeyUpdater,
        MetaStorage $metaStorage
    ) {
        $this->appState = $appState;
        $this->progress = $progress;
        $this->resultOutput = $resultOutput;
        $this->urlKeyUpdater = $urlKeyUpdater;
        $this->metaStorage = $metaStorage;

        parent::__construct();
    }

    protected function configure()
    {
        $this->setName('catalog:product:integrity:urlkey');
        $this->setDescription('Checks data integrity of the values of the url_key product attribute.');
        $this->addOption(
            'force',
            'f',
            InputOption::VALUE_NONE,
            'Force the command to run, even if it is already marked as already running'
        );

        parent::configure();
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        try {
            $this->appState->setAreaCode(AppArea::AREA_CRONTAB);
            $this->progress->setOutput($output);

            $force = $input->getOption('force');
            if ($force === true) {
                $this->metaStorage->clearStatus(UrlKeyChecker::STORAGE_IDENTIFIER);
            }

            $productData = $this->urlKeyUpdater->refresh(MetaStorage::INITIATOR_CLI);
            $cliResult = $this->resultOutput->outputResult($productData, $output);

            $output->writeln(
                "\n<info>Data was stored in cache and you can now also review it in the admin of Magento</info>"
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
