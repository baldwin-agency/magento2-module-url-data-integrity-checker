<?php

declare(strict_types=1);

namespace Baldwin\UrlDataIntegrityChecker\Console;

use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Output\OutputInterface;

class Progress
{
    private $progressBar;
    private $output;
    private $sizeByIndex;

    public function setOutput(OutputInterface $output)
    {
        $this->output = $output;
    }

    public function initProgressBar(int $redrawFequency, string $format)
    {
        if ($this->canOutput()) {
            $this->progressBar = new ProgressBar($this->output);
            $this->progressBar->setRedrawFrequency($redrawFequency);
            $this->progressBar->setFormat($format);
            $this->progressBar->start();
        }
    }

    public function setGuestimatedSize(int $nrOfIndexes, int $sizePerIndex)
    {
        for ($i = 0; $i < $nrOfIndexes; ++$i) {
            $this->sizeByIndex[$i] = $sizePerIndex;
        }
        $this->updateMaxSteps();
    }

    public function updateExpectedSize(int $index, int $size)
    {
        $this->sizeByIndex[$index] = $size;
        $this->updateMaxSteps();
    }

    public function advance()
    {
        if ($this->canOutput()) {
            $this->progressBar->advance();
        }
    }

    public function setMessage(string $message)
    {
        if ($this->canOutput()) {
            $this->progressBar->setMessage($message);
        }
    }

    public function finish()
    {
        if ($this->canOutput()) {
            $this->progressBar->finish();
        }
    }

    private function canOutput(): bool
    {
        return $this->output !== null;
    }

    private function updateMaxSteps()
    {
        if ($this->canOutput()) {
            $this->progressBar->setMaxSteps(array_sum($this->sizeByIndex));
        }
    }
}
