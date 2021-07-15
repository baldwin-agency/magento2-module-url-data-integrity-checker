<?php

declare(strict_types=1);

namespace Baldwin\UrlDataIntegrityChecker\Console;

use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Output\OutputInterface;

class Progress
{
    /** @var ProgressBar */
    private $progressBar;

    /** @var OutputInterface */
    private $output;

    private $sizeByIndex;
    private $format;

    public function __construct()
    {
        $this->sizeByIndex = [];
        $this->format = '';
    }

    public function setOutput(OutputInterface $output)
    {
        $this->output = $output;
    }

    public function initProgressBar(int $redrawFequency, string $format, string $message)
    {
        if ($this->canOutput()) {
            $this->progressBar = new ProgressBar($this->output);
            $this->progressBar->setRedrawFrequency($redrawFequency);
            $this->progressBar->setFormat($format);
            $this->progressBar->setMessage($message);
            $this->progressBar->start();

            $this->format = $format;
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
            $newMaxStepsValue = (int) array_sum($this->sizeByIndex);

            // ugly solution for the fact that the setMaxSteps method only became
            // publicly accesible in symfony/console > 4.1.0
            $reflection     = new \ReflectionObject($this->progressBar);
            $maxStepsMethod = $reflection->getMethod('setMaxSteps');
            if ($maxStepsMethod->isPrivate()) {
                $maxStepsMethod->setAccessible(true);
                $maxStepsMethod->invoke($this->progressBar, $newMaxStepsValue);
            } else {
                $this->progressBar->setMaxSteps($newMaxStepsValue);
            }

            // since symfony/console > 4.1.0, the format gets resetted after calling 'setMaxSteps'
            $this->progressBar->setFormat($this->format);
        }
    }
}
