<?php

declare(strict_types=1);

namespace Baldwin\UrlDataIntegrityChecker\Storage;

use Baldwin\UrlDataIntegrityChecker\Storage\Cache as CacheStorage;
use Magento\Framework\Stdlib\DateTime\DateTime;

class Meta
{
    const STORAGE_SUFFIX = '-meta';

    const STATUS_REFRESHING = 'refreshing';
    const STATUS_FINISHED = 'finished';

    const INITIATOR_CRON = 'cron';
    const INITIATOR_CLI = 'CLI';

    private $storage;
    private $dateTime;

    public function __construct(
        CacheStorage $storage,
        DateTime $dateTime
    ) {
        $this->storage = $storage;
        $this->dateTime = $dateTime;
    }

    public function setStartRefreshing(string $storageIdentifier, string $initiator)
    {
        $storageIdentifier .= self::STORAGE_SUFFIX;

        $this->storage->update($storageIdentifier, [
            'initiator' => $initiator,
            'started'   => $this->getCurrentTimestamp(),
            'finished'  => 0,
            'status'    => self::STATUS_REFRESHING,
        ]);
    }

    public function setFinishedRefreshing(string $storageIdentifier)
    {
        $storageIdentifier .= self::STORAGE_SUFFIX;

        $startTime = $this->getStartTime($storageIdentifier);
        $finishedTime = $this->getCurrentTimestamp();
        $executionTime = $startTime === 0 ? '?' : ($finishedTime - $startTime);

        $this->storage->update($storageIdentifier, [
            'finished'       => $finishedTime,
            'execution_time' => $executionTime,
            'status'         => self::STATUS_FINISHED,
        ]);
    }

    public function setErrorMessage(string $storageIdentifier, string $message)
    {
        $storageIdentifier .= self::STORAGE_SUFFIX;

        $this->storage->update($storageIdentifier, [
            'error' => $message,
        ]);
    }

    public function isRefreshing(string $storageIdentifier): bool
    {
        $storageIdentifier .= self::STORAGE_SUFFIX;

        $metaData = $this->storage->read($storageIdentifier);

        if (!empty($metaData) &&
            array_key_exists('status', $metaData) &&
            $metaData['status'] === self::STATUS_REFRESHING
        ) {
            return true;
        }

        return false;
    }

    public function getData(string $storageIdentifier): array
    {
        $storageIdentifier .= self::STORAGE_SUFFIX;

        return $this->storage->read($storageIdentifier);
    }

    public function clearStatus(string $storageIdentifier)
    {
        $storageIdentifier .= self::STORAGE_SUFFIX;

        return $this->storage->update($storageIdentifier, [
            'status' => '',
        ]);
    }

    private function getStartTime(string $storageIdentifier): int
    {
        $metaData = $this->storage->read($storageIdentifier);
        if (!empty($metaData) && array_key_exists('started', $metaData)) {
            return $metaData['started'];
        }

        return 0;
    }

    private function getCurrentTimestamp(): int
    {
        return $this->dateTime->gmtTimestamp();
    }
}
