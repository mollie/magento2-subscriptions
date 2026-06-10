<?php

declare(strict_types=1);

namespace Mollie\Subscriptions\Service\Mollie;

use Mollie\Api\Exceptions\ApiException;
use Mollie\Payment\Config;
use Mollie\Payment\Service\Mollie\MollieApiClient;
use Mollie\Subscriptions\Api\Data\SubscriptionToProductInterface;
use Mollie\Subscriptions\Api\SubscriptionToProductRepositoryInterface;

class CheckIfSubscriptionIsActive
{
    public function __construct(
        private readonly Config $config,
        private readonly MollieApiClient $mollieApiClient,
        private readonly SubscriptionToProductRepositoryInterface $subscriptionToProductRepository
    ) {
    }

    public function execute(SubscriptionToProductInterface $subscriptionModel): bool
    {
        $mollieApi = $this->mollieApiClient->loadByStore(storeId($subscriptionModel->getStoreId()));
        try {
            $subscription = $mollieApi->subscriptions->getForId(
                $subscriptionModel->getCustomerId(),
                $subscriptionModel->getSubscriptionId()
            );
        } catch (ApiException $exception) {
            if ($exception->getCode() == 404) {
                $this->markAsInactive($subscriptionModel);
            }

            return false;
        }

        if (!$subscription->isActive()) {
            $this->markAsInactive($subscriptionModel);
            return false;
        }

        return true;
    }

    public function markAsInactive(SubscriptionToProductInterface $subscriptionModel): void
    {
        $this->config->addToLog(
            'info',
            __(
                'Subscription with ID "%1" is not active anymore, deleting record with ID "%2"',
                $subscriptionModel->getSubscriptionId(),
                $subscriptionModel->getEntityId()
            )
        );

        $this->subscriptionToProductRepository->delete($subscriptionModel);
    }
}
