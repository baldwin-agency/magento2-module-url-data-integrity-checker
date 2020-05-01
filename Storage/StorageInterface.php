<?php

declare(strict_types=1);

namespace Baldwin\UrlDataIntegrityChecker\Storage;

interface StorageInterface
{
    /**
     * @param array<string, mixed> $data
     */
    public function write(string $identifier, array $data): bool;

    /**
     * @return array<string, mixed>
     */
    public function read(string $identifier): array;

    /**
     * @param array<string, mixed> $data
     */
    public function update(string $identifier, array $data): bool;
}
