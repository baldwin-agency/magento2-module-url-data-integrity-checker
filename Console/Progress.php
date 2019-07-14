<?php

declare(strict_types=1);

namespace Baldwin\UrlDataIntegrityChecker\Console;

use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Output\OutputInterface;

class Progress
{
    private $output;

    public function setOutput(OutputInterface $output): void
    {
        $this->output = $output;
    }

    public function initProgressBar($size, $redrawFequency, $format, $message): ProgressBar
    {
        $progress = new ProgressBar($this->output, $size);
        $progress->setRedrawFrequency($redrawFequency);
        $progress->setFormat($format);
        $progress->setMessage($message);
        $progress->start();

        return $progress;
    }
}
