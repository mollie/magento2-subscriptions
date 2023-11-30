<?php
/*
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

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
     * @var Config
     */
    private $config;

    /**
     * @var MollieSubscriptionApi
     */
    private $mollieSubscriptionApi;

    /**
     * @var ProductRepositoryInterface
     */
    private $productRepository;

    /**
     * @var PriceCurrencyInterface
     */
    private $priceCurrency;

    /**
     * @var DateTime
     */
    private $dateTime;
    /**
     * @var MollieApiClient[]
     */
    private $apiToStore = [];
    /**
     * @var Customer[]
     */
    private $customers = [];

    public function __construct(
        Config $config,
        MollieSubscriptionApi $mollieSubscriptionApi,
        ProductRepositoryInterface $productRepository,
        PriceCurrencyInterface $priceCurrency,
        DateTime $dateTime
    ) {
        $this->config = $config;
        $this->mollieSubscriptionApi = $mollieSubscriptionApi;
        $this->productRepository = $productRepository;
        $this->priceCurrency = $priceCurrency;
        $this->dateTime = $dateTime;
    }

    public function getMollieCustomer(SubscriptionToProductInterface $subscriptionToProduct): Customer
    {
        $storeId = $subscriptionToProduct->getStoreId();
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
        $api = $this->getApiForStore($subscriptionToProduct->getStoreId());
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

        $date = $this->formatDate($subscription->nextPaymentDate, $subscriptionToProduct->getStoreId());

        return [
            'subscription_id' => $subscriptionToProduct->getSubscriptionId(),
            'subscription_description' => $subscription->description,
            'subscription_nextPaymentDate' => $date,
            'subscription_amount' => $amount,
            'customer_name' => $customer->name,
            'customer_email' => $customer->email,
            'product' => $product,
        ];
    }

    private function getApiForStore($storeId): MollieApiClient
    {
        if (array_key_exists($storeId, $this->apiToStore)) {
            return $this->apiToStore[$storeId];
        }

        $this->apiToStore[$storeId] = $this->mollieSubscriptionApi->loadByStore($storeId);
        return $this->apiToStore[$storeId];
    }

    public function formatDate(string $nextPaymentDate, int $storeId): string
    {
        return $this->dateTime->date(
            $this->config->nextPaymentDateFormat($storeId),
            $nextPaymentDate
        );
    }
}
