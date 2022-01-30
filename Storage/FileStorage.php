<?php

declare(strict_types=1);

namespace Baldwin\UrlDataIntegrityChecker\Storage;

use Baldwin\UrlDataIntegrityChecker\Exception\SerializationException;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\Exception\FileSystemException;
use Magento\Framework\Filesystem;
use Magento\Framework\Filesystem\Directory\ReadFactory;
use Magento\Framework\Filesystem\Directory\WriteFactory;
use Magento\Framework\Filesystem\Driver\File as FileDriver;

class FileStorage extends AbstractStorage implements StorageInterface
{
    const CONFIG_PATH = 'url_data_integrity_checker/configuration/filestorage_path';

    private $filesystem;
    private $scopeConfig;
    private $writeFactory;
    private $readFactory;
    private $fileDriver;

    public function __construct(
        Filesystem $filesystem,
        ScopeConfigInterface $scopeConfig,
        WriteFactory $writeFactory,
        ReadFactory $readFactory,
        FileDriver $fileDriver
    ) {
        $this->filesystem = $filesystem;
        $this->scopeConfig = $scopeConfig;
        $this->writeFactory = $writeFactory;
        $this->readFactory = $readFactory;
        $this->fileDriver = $fileDriver;
    }

    public function write(string $identifier, array $data): bool
    {
        $directory = $this->writeFactory->create($this->getPath());
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
        $directory = $this->readFactory->create($this->getPath());
        $filename = $this->getFilename($identifier);

        $data = '';

        try {
            $data = $directory->readFile($filename);
        } catch (FileSystemException $ex) {
            $data = '{}';
        }

        $data = json_decode($data, true);
        if ($data === null || !is_array($data)) {
            $data = [];
        }

        return $data;
    }

    private function getFilename(string $identifier): string
    {
        return 'baldwin-url-data-integrity-checker-' . $identifier . '.json';
    }

    private function getPath(): string
    {
        $path = $this->scopeConfig->getValue(
            self::CONFIG_PATH
        );

        if ($path === '' || $path === null || !is_string($path)) {
            $path = 'var/tmp';
        }

        if ($this->fileDriver->getRealPath($path) === false) {
            $rootDir = $this->filesystem->getDirectoryRead(DirectoryList::ROOT)->getAbsolutePath();
            $path = $rootDir . '/' . $path;

            if ($this->fileDriver->getRealPath($path) === false) {
                $path = $this->filesystem->getDirectoryRead(DirectoryList::TMP)->getAbsolutePath();
            }
        } else {
            $path = $this->fileDriver->getRealPath($path);
            // hack: these next 3 lines won't ever trigger in real life, but is to satisfy phpstan
            // (I think this is a bug in phpstan)
            if ($path === true) {
                $path = '';
            }
            // end hack
        }

        return $path;
    }
}
