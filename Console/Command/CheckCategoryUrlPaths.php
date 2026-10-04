<?php

declare(strict_types=1);

namespace Baldwin\UrlDataIntegrityChecker\Console\Command;

use Baldwin\UrlDataIntegrityChecker\Console\CategoryResultOutput;
use Baldwin\UrlDataIntegrityChecker\Storage\Meta as MetaStorage;
use Baldwin\UrlDataIntegrityChecker\Updater\Catalog\Category\UrlPath as UrlPathUpdater;
use Magento\Framework\App\Area as AppArea;
use Magento\Framework\App\State as AppState;
use Magento\Framework\Console\Cli;
use Symfony\Component\Console\Command\Command as ConsoleCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class CheckCategoryUrlPaths extends ConsoleCommand
{
    private $appState;
    private $resultOutput;
    private $urlPathUpdater;

    public function __construct(
        AppState $appState,
        CategoryResultOutput $resultOutput,
        UrlPathUpdater $urlPathUpdater
    ) {
        $this->appState = $appState;
        $this->resultOutput = $resultOutput;
        $this->urlPathUpdater = $urlPathUpdater;

        parent::__construct();
    }

    protected function configure(): void
    {
        $this->setName('catalog:category:integrity:urlpath');
        $this->setDescription('Checks data integrity of the values of the url_path category attribute.');
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

            $categoryData = $this->urlPathUpdater->refresh(MetaStorage::INITIATOR_CLI);
            $cliResult = $this->resultOutput->outputResult($categoryData, $output);

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
