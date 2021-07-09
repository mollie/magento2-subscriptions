<?php
/*
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Mollie\Subscriptions\Ui\Component\Listing\Column;


use Magento\Backend\Model\UrlInterface;
use Magento\Framework\View\Element\UiComponent\ContextInterface;
use Magento\Framework\View\Element\UiComponentFactory;
use Magento\Ui\Component\Listing\Columns\Column;

class Actions extends Column
{
    /**
     * @var UrlInterface
     */
    private $urlBuilder;

    public function __construct(
        ContextInterface $context,
        UiComponentFactory $uiComponentFactory,
        UrlInterface $urlBuilder,
        array $components = [],
        array $data = [])
    {
        parent::__construct($context, $uiComponentFactory, $components, $data);
        $this->urlBuilder = $urlBuilder;
    }

    public function prepareDataSource(array $dataSource)
    {
        if (!isset($dataSource['data']['items'])) {
            return $dataSource;
        }

        $storeId = $this->getContext()->getRequestParam('filters')['store_id'] ?? null;
        foreach ($dataSource['data']['items'] as &$item) {
            if ($item['status'] != 'active') {
                continue;
            }

            $url = $this->urlBuilder->getUrl(
                'mollie_subscriptions/subscriptions/cancel',
                [
                    'store_id' => $storeId,
                    'customer_id' => $item['customer_id'],
                    'subscription_id' => $item['id'],
                ]
            );

            $item[$this->getData('name')] = [
                'view' => [
                    'href' => $url,
                    'label' => __('Cancel'),
                    'confirm' => [
                        'title' => __('Delete'),
                        'message' => __('Are you sure you want to delete this record?'),
                    ],
                ]
            ];
        }

        return $dataSource;
    }
}
