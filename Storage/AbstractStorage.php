<?php

declare(strict_types=1);

namespace Baldwin\UrlDataIntegrityChecker\Storage;

abstract class AbstractStorage implements StorageInterface
{
    public function update(string $identifier, array $data): bool
    {
        $currentData = $this->read($identifier);
        $newData = array_merge($currentData, $data);

        return $this->write($identifier, $newData);
    }
}
