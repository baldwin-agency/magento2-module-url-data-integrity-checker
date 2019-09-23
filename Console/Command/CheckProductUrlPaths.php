<?php

declare(strict_types=1);

namespace Baldwin\UrlDataIntegrityChecker\Console\Command;

use Baldwin\UrlDataIntegrityChecker\Checker\Catalog\Product\UrlPath as UrlPathChecker;
use Baldwin\UrlDataIntegrityChecker\Console\ResultOutput;
use Baldwin\UrlDataIntegrityChecker\Storage\Meta as MetaStorage;
use Baldwin\UrlDataIntegrityChecker\Updater\Catalog\Product\UrlPath as UrlPathUpdater;
use Magento\Framework\App\Area as AppArea;
use Magento\Framework\App\State as AppState;
use Magento\Framework\Console\Cli;
use Symfony\Component\Console\Command\Command as ConsoleCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class CheckProductUrlPaths extends ConsoleCommand
{
    private $appState;
    private $resultOutput;
    private $urlPathUpdater;
    private $metaStorage;

    public function __construct(
        AppState $appState,
        ResultOutput $resultOutput,
        UrlPathUpdater $urlPathUpdater,
        MetaStorage $metaStorage
    ) {
        $this->appState = $appState;
        $this->resultOutput = $resultOutput;
        $this->urlPathUpdater = $urlPathUpdater;
        $this->metaStorage = $metaStorage;

        parent::__construct();
    }

    protected function configure()
    {
        $this->setName('catalog:product:integrity:urlpath');
        $this->setDescription('Checks data integrity of the values of the url_path product attribute.');
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

            $force = $input->getOption('force');
            if ($force === true) {
                $this->metaStorage->clearStatus(UrlPathChecker::STORAGE_IDENTIFIER);
            }

            $productData = $this->urlPathUpdater->refresh(MetaStorage::INITIATOR_CLI);
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
