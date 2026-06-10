<?php
/*
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Mollie\Subscriptions\Service\Email;

use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Framework\Pricing\PriceCurrencyInterface;
use Magento\Framework\Stdlib\DateTime\DateTime;
use Mollie\Api\MollieApiClient;
use Mollie\Api\Resources\Customer;
use Mollie\Subscriptions\Api\Data\SubscriptionToProductInterface;
use Mollie\Subscriptions\Config;
use Mollie\Subscriptions\Service\Mollie\MollieSubscriptionApi;

class SubscriptionToProductEmailVariables
{
    /**
     * @var MollieApiClient[]
     */
    private $apiToStore = [];
    /**
     * @var Customer[]
     */
    private $customers = [];

    public function __construct(
        private readonly Config $config,
        private readonly MollieSubscriptionApi $mollieSubscriptionApi,
        private readonly ProductRepositoryInterface $productRepository,
        private readonly PriceCurrencyInterface $priceCurrency,
        private readonly DateTime $dateTime
    ) {
    }

    public function getMollieCustomer(SubscriptionToProductInterface $subscriptionToProduct): Customer
    {
        $storeId = storeId($subscriptionToProduct->getStoreId());
        $customerId = $subscriptionToProduct->getCustomerId();
        $key = $storeId . '-' . $customerId;
        if (array_key_exists($key, $this->customers)) {
            return $this->customers[$key];
        }

        $api = $this->getApiForStore($storeId);

        $this->customers[$key] = $api->customers->get($customerId);
        return $this->customers[$key];
    }

    public function get(SubscriptionToProductInterface $subscriptionToProduct): array
    {
        $api = $this->getApiForStore(storeId($subscriptionToProduct->getStoreId()));
        $subscription = $api->subscriptions->getForId(
            $subscriptionToProduct->getCustomerId(),
            $subscriptionToProduct->getSubscriptionId()
        );

        $product = $this->productRepository->getById($subscriptionToProduct->getProductId());
        $customer = $this->getMollieCustomer($subscriptionToProduct);
        $amount = $this->priceCurrency->format(
            $subscription->amount->value,
            false,
            PriceCurrencyInterface::DEFAULT_PRECISION,
            $subscriptionToProduct->getStoreId()
        );

        $variables = [
            'subscription_id' => $subscriptionToProduct->getSubscriptionId(),
            'subscription_description' => $subscription->description,
            'subscription_nextPaymentDate' => null,
            'subscription_amount' => $amount,
            'customer_name' => $customer->name,
            'customer_email' => $customer->email,
            'product' => $product,
        ];

        if ($subscription->nextPaymentDate !== null) {
            $date = $this->formatDate($subscription->nextPaymentDate, storeId($subscriptionToProduct->getStoreId()));
            $variables['subscription_nextPaymentDate'] = $date;
        }

        return $variables;
    }

    private function getApiForStore(?int $storeId): MollieApiClient
    {
        $key = $storeId ?? '';
        if (array_key_exists($key, $this->apiToStore)) {
            return $this->apiToStore[$key];
        }

        $this->apiToStore[$key] = $this->mollieSubscriptionApi->loadByStore($storeId);
        return $this->apiToStore[$key];
    }

    public function formatDate(string $nextPaymentDate, ?int $storeId): string
    {
        return $this->dateTime->date(
            $this->config->nextPaymentDateFormat($storeId),
            $nextPaymentDate
        );
    }
}
