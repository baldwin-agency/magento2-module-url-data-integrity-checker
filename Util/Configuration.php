<?php

declare(strict_types=1);

namespace Baldwin\UrlDataIntegrityChecker\Util;

use Magento\Framework\App\Config\ScopeConfigInterface;

class Configuration
{
    const CONFIG_ONLY_CHECK_VISIBLE_PRODUCTS = 'url_data_integrity_checker/configuration/only_check_visible_products';

    private $scopeConfig;

    public function __construct(
        ScopeConfigInterface $scopeConfig
    ) {
        $this->scopeConfig = $scopeConfig;
    }

    public function getOnlyCheckVisibleProducts(): bool
    {
        $configValue = (bool) $this->scopeConfig->getValue(
            self::CONFIG_ONLY_CHECK_VISIBLE_PRODUCTS
        );

        return $configValue;
    }
}
