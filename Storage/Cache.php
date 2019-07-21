<?php

declare(strict_types=1);

namespace Baldwin\UrlDataIntegrityChecker\Storage;

use Baldwin\UrlDataIntegrityChecker\Exception\SerializationException;
use Magento\Framework\App\CacheInterface;

class Cache
{
    private $cache;

    public function __construct(
        CacheInterface $cache
    ) {
        $this->cache = $cache;
    }

    public function write(string $identifier, array $data): bool
    {
        $encodedData = json_encode($data, JSON_UNESCAPED_UNICODE);
        if ($encodedData === false) {
            throw new SerializationException(__('Can\'t encode data as json to save in cache, error: %1', json_last_error_msg()));
        }

        return $this->cache->save($encodedData, $this->getModuleCacheIdentifier($identifier));
    }

    public function read(string $identifier): array
    {
        $data = $this->cache->load($this->getModuleCacheIdentifier($identifier)) ?: '{}';
        // TODO: figure out something better to handle empty data

        return json_decode($data, true);
    }

    private function getModuleCacheIdentifier(string $identifier): string
    {
        return 'baldwin-url-data-integrity-checker-' . $identifier;
    }
}
