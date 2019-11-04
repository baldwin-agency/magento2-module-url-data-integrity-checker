<?php

declare(strict_types=1);

namespace Baldwin\UrlDataIntegrityChecker\Storage;

use Baldwin\UrlDataIntegrityChecker\Exception\SerializationException;
use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\Exception\FileSystemException;
use Magento\Framework\Filesystem;

class FileStorage extends AbstractStorage implements StorageInterface
{
    private $filesystem;

    public function __construct(
        Filesystem $filesystem
    ) {
        $this->filesystem = $filesystem;
    }

    public function write(string $identifier, array $data): bool
    {
        $directory = $this->filesystem->getDirectoryWrite(DirectoryList::TMP);
        $directory->create();
        $filename = $this->getFilename($identifier);

        $encodedData = json_encode($data, JSON_UNESCAPED_UNICODE);
        if ($encodedData === false) {
            throw new SerializationException(
                __('Can\'t encode data as json to save in cache, error: %1', json_last_error_msg())
            );
        }

        $directory->writeFile($filename, $encodedData); // will throw FileSystemException when file can't be written

        return true;
    }

    public function read(string $identifier): array
    {
        $directory = $this->filesystem->getDirectoryRead(DirectoryList::TMP);
        $filename = $this->getFilename($identifier);

        $data = '';

        try {
            $data = $directory->readFile($filename);
        } catch (FileSystemException $ex) {
            $data = '{}';
        }

        return json_decode($data, true);
    }

    private function getFilename(string $identifier): string
    {
        return 'baldwin-url-data-integrity-checker-' . $identifier . '.json';
    }
}
