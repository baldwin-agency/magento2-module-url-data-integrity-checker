<?php

declare(strict_types=1);

namespace Baldwin\UrlDataIntegrityChecker\Block\Adminhtml;

use Baldwin\UrlDataIntegrityChecker\Exception\MissingConfigurationException;
use Baldwin\UrlDataIntegrityChecker\Storage\Meta as MetaStorage;
use Magento\Backend\Block\Template;
use Magento\Backend\Block\Template\Context as BackendBlockContext;

class Metadata extends Template
{
    protected $_template = 'Baldwin_UrlDataIntegrityChecker::metadata.phtml';

    private $metaStorage;

    public function __construct(
        BackendBlockContext $context,
        MetaStorage $metaStorage,
        array $data = []
    ) {
        parent::__construct($context, $data);

        $this->metaStorage = $metaStorage;
    }

    public function getMetadata()
    {
        $storageIdentifier = $this->getStorageIdentifier();
        if ($storageIdentifier === null || $storageIdentifier === '') {
            throw new MissingConfigurationException(__('No storage identifier was setup for this block!'));
        }

        $metaData = $this->metaStorage->getData($storageIdentifier);
        $metaData = $this->format($metaData);

        return $metaData;
    }

    private function format(array $metaData): array
    {
        $formatted = [];

        foreach ($metaData as $key => $value) {
            switch ($key) {
                case 'started':
                case 'finished':
                    $formatted[$key] = $this->_localeDate->formatDateTime(
                        $this->_localeDate->date($value),
                        \IntlDateFormatter::MEDIUM
                    );
                    break;
                case 'execution_time':
                    $formatted[$key] = __('%1 seconds', $value);
                    break;
                default:
                    $formatted[$key] = $value;
            }
        }

        if (array_key_exists('status', $metaData)) {
            if ($metaData['status'] === MetaStorage::STATUS_REFRESHING) {
                unset($formatted['finished']);
                unset($formatted['execution_time']);
            } elseif ($metaData['status'] === MetaStorage::STATUS_FINISHED) {
                unset($formatted['error']);
            }
        }

        return $formatted;
    }
}
