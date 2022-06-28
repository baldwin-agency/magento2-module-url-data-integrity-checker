<?php

declare(strict_types=1);

namespace Baldwin\UrlDataIntegrityChecker\Ui\Component\Listing\Columns;

use Magento\Framework\UrlInterface;
use Magento\Framework\View\Element\UiComponent\ContextInterface;
use Magento\Framework\View\Element\UiComponentFactory;
use Magento\Framework\View\Element\UiComponentInterface;
use Magento\Ui\Component\Listing\Columns\Column;

class CategoryActions extends Column
{
    private $urlBuilder;

    /**
     * @param array<UiComponentInterface> $components
     * @param array<mixed> $data
     */
    public function __construct(
        ContextInterface $context,
        UiComponentFactory $uiComponentFactory,
        UrlInterface $urlBuilder,
        array $components = [],
        array $data = []
    ) {
        $this->urlBuilder = $urlBuilder;

        parent::__construct($context, $uiComponentFactory, $components, $data);
    }

    /**
     * @param array{
     *  data: ?array{
     *   items: array{
     *    array{
     *     catId: int,
     *     name: string,
     *     storeId: int,
     *     problem: string,
     *     hash: string
     *    }
     *   },
     *   totalRecords: int
     *  }
     * } $dataSource
     *
     * @return array{
     *  data: ?array{
     *   items: array{
     *    array{
     *     catId: int,
     *     name: string,
     *     storeId: int,
     *     problem: string,
     *     hash: string
     *    }
     *   },
     *   totalRecords: int
     *  }
     * }
     */
    public function prepareDataSource(array $dataSource)
    {
        if (isset($dataSource['data']['items'])) {
            foreach ($dataSource['data']['items'] as &$item) {
                $item[$this->getData('name')]['edit'] = [
                    'href'      => $this->urlBuilder->getUrl(
                        'catalog/category/edit',
                        ['id' => $item['catId'], 'store' => $item['storeId']]
                    ),
                    'ariaLabel' => __('Edit ') . $item['name'],
                    'label'     => __('Edit'),
                    'hidden'    => false,
                ];
            }
        }

        return $dataSource;
    }
}
