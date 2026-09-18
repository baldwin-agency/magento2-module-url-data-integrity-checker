<?php

declare(strict_types=1);

namespace Baldwin\UrlDataIntegrityChecker\Storage;

use Baldwin\UrlDataIntegrityChecker\Exception\AlreadyRefreshingException;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Stdlib\DateTime\DateTime;

class Meta
{
    public const STORAGE_SUFFIX = '-meta';

    public const STATUS_PENDING = 'pending';
    public const STATUS_REFRESHING = 'refreshing';
    public const STATUS_FINISHED = 'finished';

    public const INITIATOR_CRON = 'cron';
    public const INITIATOR_CLI = 'CLI';

    public const CONFIG_PATH_REFRESH_TIMEOUT = 'url_data_integrity_checker/configuration/refresh_timeout';
    public const DEFAULT_REFRESH_TIMEOUT = 21600; // 6 hours

    private $storage;
    private $dateTime;
    private $scopeConfig;

    public function __construct(
        StorageInterface $storage,
        DateTime $dateTime,
        ScopeConfigInterface $scopeConfig
    ) {
        $this->storage = $storage;
        $this->dateTime = $dateTime;
        $this->scopeConfig = $scopeConfig;
    }

    public function setPending(string $storageIdentifier, string $initiator): void
    {
        if ($this->isRefreshing($storageIdentifier)) {
            throw new AlreadyRefreshingException(__(
                'We are already refreshing this checker. '
                . 'If you believe this is an error, clear it by providing the \'--force\' flag using the command line '
                . 'in the appropriate integrity check command'
            ));
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

    public function setErrorMessage(string $storageIdentifier, string $message): void
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

        if (empty($metaData)
            || !array_key_exists('status', $metaData)
            || $metaData['status'] !== self::STATUS_REFRESHING
        ) {
            return false;
        }

        // a refresh which was started but never finished, keeps the checker marked as 'refreshing' forever,
        // which blocks every following run. This can happen when the process running the checker is killed
        // without being able to clean up after itself (out of memory, a timeout, a deploy, ...).
        // So we treat a refresh which is running for longer than the configured timeout as gone,
        // and allow a new refresh to start.
        if ($this->hasRefreshTimedOut($storageIdentifier)) {
            $this->storage->update($storageIdentifier, [
                'status' => '',
            ]);

            return false;
        }

        return true;
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

    private function hasRefreshTimedOut(string $storageIdentifier): bool
    {
        $timeout = $this->getRefreshTimeout();

        // a timeout of zero or lower disables this behaviour
        if ($timeout <= 0) {
            return false;
        }

        $startTime = $this->getStartTime($storageIdentifier);

        // we don't know when this refresh was started, so we can't tell if it's still running
        if ($startTime === 0) {
            return true;
        }

        return $this->getCurrentTimestamp() - $startTime >= $timeout;
    }

    private function getRefreshTimeout(): int
    {
        $timeout = $this->scopeConfig->getValue(self::CONFIG_PATH_REFRESH_TIMEOUT);

        if (!is_numeric($timeout)) {
            return self::DEFAULT_REFRESH_TIMEOUT;
        }

        return (int) $timeout;
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
}
