<?php

declare(strict_types=1);

namespace Baldwin\UrlDataIntegrityChecker\Console\Command;

use Baldwin\UrlDataIntegrityChecker\Checker\Catalog\Product\UrlPath as UrlPathChecker;
use Baldwin\UrlDataIntegrityChecker\Console\ResultOutput;
use Baldwin\UrlDataIntegrityChecker\Storage\Cache as CacheStorage;
use Magento\Framework\App\Area as AppArea;
use Magento\Framework\App\State as AppState;
use Magento\Framework\Console\Cli;
use Symfony\Component\Console\Command\Command as ConsoleCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class CheckProductUrlPaths extends ConsoleCommand
{
    private $appState;
    private $resultOutput;
    private $urlPathChecker;
    private $storage;

    public function __construct(
        AppState $appState,
        ResultOutput $resultOutput,
        UrlPathChecker $urlPathChecker,
        CacheStorage $storage
    ) {
        $this->appState = $appState;
        $this->resultOutput = $resultOutput;
        $this->urlPathChecker = $urlPathChecker;
        $this->storage = $storage;

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
            $stored = $this->storage->write(UrlPathChecker::STORAGE_IDENTIFIER, $productData);
            $cliResult = $this->resultOutput->outputResult($productData, $output);

            if ($stored) {
                $output->writeln(
                    "\n<info>Data was stored in cache and you can now also review it in the admin of Magento</info>"
                );
            }

            return $cliResult;
        } catch (\Throwable $ex) {
            $output->writeln("<error>An unexpected exception occured: '{$ex->getMessage()}'</error>");
        }

        return Cli::RETURN_FAILURE;
    }
}
