<?php

declare(strict_types=1);

namespace Baldwin\UrlDataIntegrityChecker\Ui\Component\Listing\Column;

use Magento\Theme\Ui\Component\Listing\Column\EditAction as ColumnEditAction;

class EditAction extends ColumnEditAction
{
    /**
     * @param array{
     *  data: ?array{
     *   items: array{
     *    array<string, mixed>
     *   },
     *   totalRecords: int
     *  }
     * } $dataSource
     *
     * @return array{
     *  data: ?array{
     *   items: array{
     *    array<string, mixed>
     *   },
     *   totalRecords: int
     *  }
     * }
     */
    public function prepareDataSource(array $dataSource)
    {
        $indexField  = $this->getData('config/indexField');
        $editUrlPath = $this->getData('config/editUrlPath');

        if (!is_string($indexField) || $indexField === '' || !is_string($editUrlPath) || $editUrlPath === '') {
            return $dataSource;
        }

        if (isset($dataSource['data']['items'])) {
            foreach ($dataSource['data']['items'] as &$item) {
                if (isset($item[$indexField])) {
                    $entityId = $item[$indexField];

                    $storeId = null;
                    if (isset($item['storeId'])) {
                        $storeId = $item['storeId'];
                    }

                    $item[$this->getData('name')] = [
                        'edit' => [
                            'href'  => $this->urlBuilder->getUrl(
                                $editUrlPath,
                                [
                                    'id'    => $entityId,
                                    'store' => $storeId,
                                ]
                            ),
                            'label' => __('Edit'),
                        ],
                    ];
                }
            }
        }

        return $dataSource;
    }
}
