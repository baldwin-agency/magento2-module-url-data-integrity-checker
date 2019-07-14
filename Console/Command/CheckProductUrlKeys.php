<?php

declare(strict_types=1);

namespace Baldwin\UrlDataIntegrityChecker\Console\Command;

use Baldwin\UrlDataIntegrityChecker\Checker\Catalog\Product\UrlKey as UrlKeyChecker;
use Baldwin\UrlDataIntegrityChecker\Console\ResultOutput;
use Baldwin\UrlDataIntegrityChecker\Console\Progress;
use Magento\Framework\App\Area as AppArea;
use Magento\Framework\App\State as AppState;
use Symfony\Component\Console\Command\Command as ConsoleCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class CheckProductUrlKeys extends ConsoleCommand
{
    private $appState;
    private $progress;
    private $resultOutput;
    private $urlKeyChecker;

    public function __construct(
        AppState $appState,
        Progress $progress,
        ResultOutput $resultOutput,
        UrlKeyChecker $urlKeyChecker
    ) {
        $this->appState = $appState;
        $this->progress = $progress;
        $this->resultOutput = $resultOutput;
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
            $this->resultOutput->outputResult($productData, $output);
        } catch (\Throwable $ex) {
            $output->writeln("<error>An unexpected exception occured: '{$ex->getMessage()}'</error>");
        }
    }
}
