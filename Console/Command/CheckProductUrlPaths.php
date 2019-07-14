<?php

declare(strict_types=1);

namespace Baldwin\UrlDataIntegrityChecker\Console\Command;

use Baldwin\UrlDataIntegrityChecker\Checker\Catalog\Product\UrlPath as UrlPathChecker;
use Baldwin\UrlDataIntegrityChecker\Console\ResultOutput;
use Magento\Framework\App\Area as AppArea;
use Magento\Framework\App\State as AppState;
use Symfony\Component\Console\Command\Command as ConsoleCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class CheckProductUrlPaths extends ConsoleCommand
{
    private $appState;
    private $resultOutput;
    private $urlPathChecker;

    public function __construct(
        AppState $appState,
        ResultOutput $resultOutput,
        UrlPathChecker $urlPathChecker
    ) {
        $this->appState = $appState;
        $this->resultOutput = $resultOutput;
        $this->urlPathChecker = $urlPathChecker;

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
            $this->appState->setAreaCode(AppArea::AREA_CRONTAB);

            $productData = $this->urlPathChecker->execute();
            $this->resultOutput->outputResult($productData, $output);
        } catch (\Throwable $ex) {
            $output->writeln("<error>An unexpected exception occured: '{$ex->getMessage()}'</error>");
        }
    }
}
