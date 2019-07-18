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

    public function write(string $identifier, array $data)
    {
        $encodedData = json_encode($data, JSON_UNESCAPED_UNICODE);
        if ($encodedData === false) {
            throw new SerializationException(__('Can\'t encode data as json to save in cache, error: %1', json_last_error_msg()));
        }

        $this->cache->save($encodedData, $this->getModuleCacheIdentifier($identifier));
    }

    public function read(string $identifier): array
    {
        return json_decode($this->cache->load($this->getModuleCacheIdentifier($identifier)), true);
    }

    private function getModuleCacheIdentifier(string $identifier): string
    {
        return 'baldwin-url-data-integrity-checker-' . $identifier;
    }
}
