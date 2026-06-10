<?php
/*
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Mollie\Subscriptions\Model\UpdatePrice;

use Magento\Framework\App\Request\DataPersistorInterface;
use Magento\Framework\App\RequestInterface;
use Magento\Ui\DataProvider\AbstractDataProvider;
use Mollie\Subscriptions\Api\SubscriptionToProductRepositoryInterface;
use Mollie\Subscriptions\Service\Mollie\MollieSubscriptionApi;
use Psr\Log\LoggerInterface;

class DataProvider extends AbstractDataProvider
{
    public function __construct(
        $name,
        $primaryFieldName,
        $requestFieldName,
        private readonly RequestInterface $request,
        private readonly DataPersistorInterface $dataPersistor,
        private readonly SubscriptionToProductRepositoryInterface $subscriptionRepository,
        private readonly MollieSubscriptionApi $mollieApi,
        private readonly LoggerInterface $logger,
        array $meta = [],
        array $data = []
    ) {
        parent::__construct($name, $primaryFieldName, $requestFieldName, $meta, $data);
    }

    public function getData()
    {
        $customerId = $this->request->getParam('customer_id');
        $subscriptionId = $this->request->getParam('subscription_id');

        if (!$customerId || !$subscriptionId) {
            return [];
        }

        $data = $this->dataPersistor->get('mollie_subscription_update_price');
        if (!empty($data)) {
            $this->dataPersistor->clear('mollie_subscription_update_price');
            return [$subscriptionId => $data];
        }

        try {
            // Load subscription from local database
            $subscription = $this->subscriptionRepository->getBySubscriptionId($subscriptionId);

            // Load current price from Mollie API
            $mollieClient = $this->mollieApi->loadByStore();
            $mollieSubscription = $mollieClient->subscriptions->getForId($customerId, $subscriptionId);

            $formData = [
                'subscription_id' => $subscriptionId,
                'customer_id' => $customerId,
                'current_price' => $mollieSubscription->amount->value,
                'new_price' => ''
            ];

            return [$subscriptionId => $formData];
        } catch (\Exception $e) {
            $this->logger->error('Error loading subscription data: ' . $e->getMessage());
            return [];
        }
    }

    public function addFilter(\Magento\Framework\Api\Filter $filter)
    {
        return;
    }
}
