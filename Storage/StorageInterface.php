<?php

declare(strict_types=1);

namespace Baldwin\UrlDataIntegrityChecker\Storage;

interface StorageInterface
{
    public function write(string $identifier, array $data): bool;

    public function read(string $identifier): array;

    public function update(string $identifier, array $data): bool;
}
