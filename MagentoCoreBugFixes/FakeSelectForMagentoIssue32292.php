<?php

declare(strict_types=1);

namespace Baldwin\UrlDataIntegrityChecker\MagentoCoreBugFixes;

/**
 * Silly workaround for core Magento bug introduced in 2.4.2
 * - https://github.com/magento/magento2/issues/32292
 * - https://github.com/baldwin-agency/magento2-module-url-data-integrity-checker/issues/16
 */
class FakeSelectForMagentoIssue32292
{
    /**
     * @return array<mixed>
     */
    public function getPart(string $part): array
    {
        return [];
    }
}
