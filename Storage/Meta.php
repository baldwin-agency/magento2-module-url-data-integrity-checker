<?php

declare(strict_types=1);

namespace Baldwin\UrlDataIntegrityChecker\Storage;

use Baldwin\UrlDataIntegrityChecker\Exception\AlreadyRefreshingException;
use Magento\Framework\Lock\LockManagerInterface;
use Magento\Framework\Stdlib\DateTime\DateTime;

class Meta
{
    public const STORAGE_SUFFIX = '-meta';

    public const STATUS_PENDING = 'pending';
    public const STATUS_REFRESHING = 'refreshing';
    public const STATUS_FINISHED = 'finished';

    public const INITIATOR_CRON = 'cron';
    public const INITIATOR_CLI = 'CLI';

    private $storage;
    private $dateTime;
    private $lockManager;

    public function __construct(
        StorageInterface $storage,
        DateTime $dateTime,
        LockManagerInterface $lockManager
    ) {
        $this->storage = $storage;
        $this->dateTime = $dateTime;
        $this->lockManager = $lockManager;
    }

    public function setPending(string $storageIdentifier, string $initiator): void
    {
        if ($this->isRefreshing($storageIdentifier)) {
            throw new AlreadyRefreshingException(__('We are already refreshing this checker.'));
        }

        $storageIdentifier .= self::STORAGE_SUFFIX;

        $this->storage->update($storageIdentifier, [
            'initiator' => $initiator,
            'started'   => 0,
            'finished'  => 0,
            'status'    => self::STATUS_PENDING,
        ]);
    }

    public function setStartRefreshing(string $storageIdentifier, string $initiator): void
    {
        $lockName = $this->getLockName($storageIdentifier);
        if ($this->lockManager->lock($lockName, 0) === false) {
            throw new AlreadyRefreshingException(__('We are already refreshing this checker.'));
        }

        $storageIdentifier .= self::STORAGE_SUFFIX;

        $this->storage->update($storageIdentifier, [
            'initiator' => $initiator,
            'started'   => $this->getCurrentTimestamp(),
            'finished'  => 0,
            'status'    => self::STATUS_REFRESHING,
        ]);
    }

    public function setFinishedRefreshing(string $storageIdentifier): void
    {
        $lockName = $this->getLockName($storageIdentifier);
        $storageIdentifier .= self::STORAGE_SUFFIX;

        $startTime = $this->getStartTime($storageIdentifier);
        $finishedTime = $this->getCurrentTimestamp();
        $executionTime = $startTime === 0 ? '?' : ($finishedTime - $startTime);

        $this->storage->update($storageIdentifier, [
            'finished'       => $finishedTime,
            'execution_time' => $executionTime,
            'status'         => self::STATUS_FINISHED,
        ]);

        $this->lockManager->unlock($lockName);
    }

    public function setErrorMessage(string $storageIdentifier, string $message): void
    {
        $storageIdentifier .= self::STORAGE_SUFFIX;

        $this->storage->update($storageIdentifier, [
            'error' => $message,
        ]);
    }

    public function isRefreshing(string $storageIdentifier): bool
    {
        $lockName = $this->getLockName($storageIdentifier);

        return $this->lockManager->isLocked($lockName);
    }

    /**
     * @return array<string, mixed>
     */
    public function getData(string $storageIdentifier): array
    {
        $storageIdentifier .= self::STORAGE_SUFFIX;

        return $this->storage->read($storageIdentifier);
    }

    public function clearStatus(string $storageIdentifier): bool
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
            assert(is_int($metaData['started']));

            return $metaData['started'];
        }

        return 0;
    }

    private function getCurrentTimestamp(): int
    {
        return $this->dateTime->gmtTimestamp();
    }

    private function getLockName(string $storageIdentifier): string
    {
        return sprintf('Baldwin_UrlDataIntegrityChecker_%s', $storageIdentifier);
    }
}
