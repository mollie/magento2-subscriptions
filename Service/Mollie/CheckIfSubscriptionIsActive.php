<?php

namespace Mollie\Subscriptions\Service\Mollie;

use Mollie\Payment\Config;
use Mollie\Payment\Service\Mollie\MollieApiClient;
use Mollie\Subscriptions\Api\Data\SubscriptionToProductInterface;
use Mollie\Subscriptions\Api\SubscriptionToProductRepositoryInterface;

class CheckIfSubscriptionIsActive
{
    /**
     * @var Config
     */
    private $config;

    /**
     * @var MollieApiClient
     */
    private $mollieApiClient;

    /**
     * @var SubscriptionToProductRepositoryInterface
     */
    private $subscriptionToProductRepository;

    public function __construct(
        Config $config,
        MollieApiClient $mollieApiClient,
        SubscriptionToProductRepositoryInterface $subscriptionToProductRepository
    ) {
        $this->config = $config;
        $this->mollieApiClient = $mollieApiClient;
        $this->subscriptionToProductRepository = $subscriptionToProductRepository;
    }

    public function execute(SubscriptionToProductInterface $subscriptionModel): bool
    {
        $mollieApi = $this->mollieApiClient->loadByStore($subscriptionModel->getStoreId());
        $subscription = $mollieApi->subscriptions->getForId(
            $subscriptionModel->getCustomerId(),
            $subscriptionModel->getSubscriptionId()
        );

        if (!$subscription->isActive()) {
            $this->config->addToLog(
                'info',
                __(
                    'Subscription with ID "%1" is not active anymore, deleting record with ID "%2"',
                    $subscriptionModel->getSubscriptionId(),
                    $subscriptionModel->getEntityId()
                )
            );

            $this->subscriptionToProductRepository->delete($subscriptionModel);
            return false;
        }

        return true;
    }
}
