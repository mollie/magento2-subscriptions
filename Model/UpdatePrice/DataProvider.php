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
    /**
     * @var RequestInterface
     */
    private $request;
    /**
     * @var DataPersistorInterface
     */
    private $dataPersistor;
    /**
     * @var SubscriptionToProductRepositoryInterface
     */
    private $subscriptionRepository;
    /**
     * @var MollieSubscriptionApi
     */
    private $mollieApi;
    /**
     * @var LoggerInterface
     */
    private $logger;

    public function __construct(
        $name,
        $primaryFieldName,
        $requestFieldName,
        RequestInterface $request,
        DataPersistorInterface $dataPersistor,
        SubscriptionToProductRepositoryInterface $subscriptionRepository,
        MollieSubscriptionApi $mollieApi,
        LoggerInterface $logger,
        array $meta = [],
        array $data = []
    ) {
        $this->request = $request;
        $this->dataPersistor = $dataPersistor;
        $this->subscriptionRepository = $subscriptionRepository;
        $this->mollieApi = $mollieApi;
        $this->logger = $logger;
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
