<?php

declare(strict_types=1);

namespace Baldwin\UrlDataIntegrityChecker\Test\Storage;

use Baldwin\UrlDataIntegrityChecker\Storage\CacheStorage;
use Magento\Framework\App\Cache as AppCache;
use PHPUnit\Framework\TestCase;

class CacheStorageTest extends TestCase
{
    public function testUpdatingNonExisting(): void
    {
        $identifier = 'identifier';
        $data = [
            'key1' => 'value1',
            'key2' => 'value2',
        ];

        $cacheMock = $this
            ->getMockBuilder(AppCache::class)
            ->disableOriginalConstructor()
            ->getMock();

        $cacheMock->expects($this->once())
            ->method('save')
            ->with($this->jsonEncode($data))
            ->willReturn(true);

        $cacheMock->expects($this->exactly(2))
            ->method('load')
            ->willReturn($this->jsonEncode($data));

        $cacheStorage = new CacheStorage($cacheMock);
        $cacheStorage->update($identifier, $data);

        $this->assertEquals($data, $cacheStorage->read($identifier));
    }

    public function testUpdatingExisting(): void
    {
        $identifier = 'identifier';
        $existingData = [
            'key1' => 'value1',
            'key2' => 'value2',
        ];
        $newData = [
            'key2' => 'value2-updated',
            'key3' => 'value3',
        ];
        $expectedData = [
            'key1' => 'value1',
            'key2' => 'value2-updated',
            'key3' => 'value3',
        ];

        $cacheMock = $this
            ->getMockBuilder(AppCache::class)
            ->disableOriginalConstructor()
            ->getMock();

        $matcher = $this->exactly(2);
        $cacheMock->expects($matcher)
            ->method('save')
            // replacement for withConsecutive,
            // using https://github.com/sebastianbergmann/phpunit/issues/4026#issuecomment-1556763479
            ->willReturnCallback(function (string $param) use ($matcher, $existingData, $expectedData) {
                match ($matcher->numberOfInvocations()) {
                    1       => $this->assertEquals($param, $this->jsonEncode($existingData)),
                    2       => $this->assertEquals($param, $this->jsonEncode($expectedData)),
                    default => throw new \LogicException('unexpected amount of calls'),
                };

                return true;
            });

        $cacheMock->expects($this->exactly(2))
            ->method('load')
            ->willReturn(
                $this->jsonEncode($existingData),
                $this->jsonEncode($expectedData),
            );

        $cacheStorage = new CacheStorage($cacheMock);
        $cacheStorage->write($identifier, $existingData);
        $cacheStorage->update($identifier, $newData);

        $this->assertEquals($expectedData, $cacheStorage->read($identifier));
    }

    public function testClear(): void
    {
        $identifier = 'identifier';
        $existingData = [
            'key1' => 'value1',
            'key2' => 'value2',
        ];

        $expectedData = [];

        $cacheMock = $this
            ->getMockBuilder(AppCache::class)
            ->disableOriginalConstructor()
            ->getMock();

        $matcher = $this->exactly(2);
        $cacheMock->expects($matcher)
            ->method('save')
            // replacement for withConsecutive,
            // using https://github.com/sebastianbergmann/phpunit/issues/4026#issuecomment-1556763479
            ->willReturnCallback(function (string $param) use ($matcher, $existingData, $expectedData) {
                match ($matcher->numberOfInvocations()) {
                    1       => $this->assertEquals($param, $this->jsonEncode($existingData)),
                    2       => $this->assertEquals($param, $this->jsonEncode($expectedData)),
                    default => throw new \LogicException('unexpected amount of calls'),
                };

                return true;
            });

        $cacheMock->expects($this->exactly(1))
            ->method('load')
            ->willReturn($this->jsonEncode($expectedData));

        $cacheStorage = new CacheStorage($cacheMock);
        $cacheStorage->write($identifier, $existingData);
        $cacheStorage->clear($identifier);

        $this->assertEquals($expectedData, $cacheStorage->read($identifier));
    }

    /**
     * @param array<string, string> $data
     */
    private function jsonEncode(array $data): string
    {
        $data = json_encode($data, JSON_UNESCAPED_UNICODE);
        // this check is just to satisfies phpstan checks
        if ($data === false) {
            throw new \Exception(sprintf('Can\'t encode data as json, error: %s', json_last_error_msg()));
        }

        return $data;
    }
}
