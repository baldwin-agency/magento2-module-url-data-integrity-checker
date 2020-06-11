<?php

declare(strict_types=1);

namespace Baldwin\UrlDataIntegrityChecker\Cron;

use Magento\Cron\Model\ResourceModel\Schedule\Collection as CronScheduleCollection;
use Magento\Cron\Model\Schedule as CronScheduleModel;
use Magento\Framework\App\ProductMetadataInterface;
use Magento\Framework\Stdlib\DateTime\DateTime;
use Magento\Framework\Stdlib\DateTime\TimezoneInterface;

class ScheduleJob
{
    private $productMetadata;
    private $dateTime;
    private $timezone;
    private $cronScheduleCollection;

    /**
     * @param CronScheduleCollection<CronScheduleModel> $cronScheduleCollection
     */
    public function __construct(
        ProductMetadataInterface $productMetadata,
        DateTime $dateTime,
        TimezoneInterface $timezone,
        CronScheduleCollection $cronScheduleCollection
    ) {
        $this->productMetadata = $productMetadata;
        $this->dateTime = $dateTime;
        $this->timezone = $timezone;
        $this->cronScheduleCollection = $cronScheduleCollection;
    }

    public function schedule(string $jobCode): bool
    {
        $createdAtTime = $this->getCronTimestamp();
        $scheduledAtTime = $createdAtTime + (60 - ($createdAtTime % 60)); // set scheduledAtTime to next minute

        /** @var CronScheduleModel */
        $schedule = $this->cronScheduleCollection->getNewEmptyItem();
        $schedule
            ->setJobCode($jobCode)
            ->setStatus(CronScheduleModel::STATUS_PENDING)
            ->setCreatedAt(strftime('%Y-%m-%d %H:%M:%S', $createdAtTime))
            ->setScheduledAt(strftime('%Y-%m-%d %H:%M', $scheduledAtTime))
            ->save();

        return true;
    }

    /**
     * Get timestamp used for time related database fields in the cron tables
     *
     * Note: The timestamp used got changed from Magento 2.1.* to 2.2.* and
     *       these changes are branched by Magento version in this method.
     *       ref: https://github.com/netz98/n98-magerun2/issues/296
     */
    private function getCronTimestamp(): int
    {
        $version = $this->productMetadata->getVersion();

        // When running on a version of Magento in development by cloning the repo,
        // the version will be for example 'dev-2.4-develop',
        // stripping of 'dev-' from the start will still execute the correct version check here
        // '?:' exists to satisfy static tests because preg_replace could potentially return 'null'
        $version = preg_replace('/^dev\-/', '', $version) ?: $version;

        if (version_compare($version, '2.2.0') >= 0) {
            return $this->dateTime->gmtTimestamp();
        }

        return $this->timezone->scopeTimeStamp();
    }
}
