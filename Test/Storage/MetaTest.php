<?php

declare(strict_types=1);

namespace Baldwin\UrlDataIntegrityChecker\Test\Storage;

use Baldwin\UrlDataIntegrityChecker\Storage\Meta;
use Baldwin\UrlDataIntegrityChecker\Storage\StorageInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Stdlib\DateTime\DateTime;
use PHPUnit\Framework\TestCase;

class MetaTest extends TestCase
{
    private const IDENTIFIER = 'identifier';
    private const NOW = 1700000000;

    public function testIsRefreshingWhileRefreshIsStillRunning(): void
    {
        $meta = $this->createMeta(
            [
                'started' => self::NOW - 60,
                'status'  => Meta::STATUS_REFRESHING,
            ],
            null,
            $this->never()
        );

        $this->assertTrue($meta->isRefreshing(self::IDENTIFIER));
    }

    public function testIsNotRefreshingWhenRefreshTimedOut(): void
    {
        $meta = $this->createMeta(
            [
                'started' => self::NOW - Meta::DEFAULT_REFRESH_TIMEOUT - 1,
                'status'  => Meta::STATUS_REFRESHING,
            ],
            null,
            $this->once()
        );

        $this->assertFalse($meta->isRefreshing(self::IDENTIFIER));
    }

    public function testIsNotRefreshingWhenStartTimeIsUnknown(): void
    {
        $meta = $this->createMeta(
            [
                'status' => Meta::STATUS_REFRESHING,
            ],
            null,
            $this->once()
        );

        $this->assertFalse($meta->isRefreshing(self::IDENTIFIER));
    }

    public function testTimedOutRefreshKeepsBlockingWhenTimeoutIsDisabled(): void
    {
        $meta = $this->createMeta(
            [
                'started' => self::NOW - Meta::DEFAULT_REFRESH_TIMEOUT - 1,
                'status'  => Meta::STATUS_REFRESHING,
            ],
            '0',
            $this->never()
        );

        $this->assertTrue($meta->isRefreshing(self::IDENTIFIER));
    }

    public function testConfiguredTimeoutIsUsed(): void
    {
        $meta = $this->createMeta(
            [
                'started' => self::NOW - 120,
                'status'  => Meta::STATUS_REFRESHING,
            ],
            '60',
            $this->once()
        );

        $this->assertFalse($meta->isRefreshing(self::IDENTIFIER));
    }

    public function testIsNotRefreshingWithoutStoredData(): void
    {
        $meta = $this->createMeta([], null, $this->never());

        $this->assertFalse($meta->isRefreshing(self::IDENTIFIER));
    }

    /**
     * @param array<string, mixed> $metaData
     */
    private function createMeta(
        array $metaData,
        ?string $configuredTimeout,
        \PHPUnit\Framework\MockObject\Rule\InvocationOrder $expectedStatusClears
    ): Meta {
        $storageMock = $this->createMock(StorageInterface::class);
        $storageMock
            ->method('read')
            ->with(self::IDENTIFIER . Meta::STORAGE_SUFFIX)
            ->willReturn($metaData);
        $storageMock
            ->expects($expectedStatusClears)
            ->method('update')
            ->with(self::IDENTIFIER . Meta::STORAGE_SUFFIX, ['status' => ''])
            ->willReturn(true);

        $dateTimeMock = $this
            ->getMockBuilder(DateTime::class)
            ->disableOriginalConstructor()
            ->getMock();
        $dateTimeMock
            ->method('gmtTimestamp')
            ->willReturn(self::NOW);

        $scopeConfigMock = $this->createMock(ScopeConfigInterface::class);
        $scopeConfigMock
            ->method('getValue')
            ->with(Meta::CONFIG_PATH_REFRESH_TIMEOUT)
            ->willReturn($configuredTimeout);

        return new Meta($storageMock, $dateTimeMock, $scopeConfigMock);
    }
}
