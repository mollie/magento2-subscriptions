<?php
/*
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Mollie\Subscriptions\Service\Email;

use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Framework\Pricing\PriceCurrencyInterface;
use Mollie\Api\MollieApiClient;
use Mollie\Api\Resources\Customer;
use Mollie\Payment\Model\Mollie;
use Mollie\Subscriptions\Api\Data\SubscriptionToProductInterface;

class SubscriptionToProductEmailVariables
{
    /**
     * @var Mollie
     */
    private $mollie;

    /**
     * @var Customer|null
     */
    private $customer;

    /**
     * @var ProductRepositoryInterface
     */
    private $productRepository;

    /**
     * @var PriceCurrencyInterface
     */
    private $priceCurrency;

    /**
     * @var MollieApiClient[]
     */
    private $apiToStore = [];

    public function __construct(
        Mollie $mollie,
        ProductRepositoryInterface $productRepository,
        PriceCurrencyInterface $priceCurrency
    ) {
        $this->mollie = $mollie;
        $this->productRepository = $productRepository;
        $this->priceCurrency = $priceCurrency;
    }

    public function getMollieCustomer(SubscriptionToProductInterface $subscriptionToProduct): Customer
    {
        if ($this->customer) {
            return $this->customer;
        }

        $api = $this->getApiForStore($subscriptionToProduct->getStoreId());

        $this->customer = $api->customers->get($subscriptionToProduct->getCustomerId());
        return $this->customer;
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

        return [
            'subscription_id' => $subscriptionToProduct->getSubscriptionId(),
            'subscription_description' => $subscription->description,
            'subscription_nextPaymentDate' => $subscription->nextPaymentDate,
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

        $this->apiToStore[$storeId] = $this->mollie->getMollieApi($storeId);
        return $this->apiToStore[$storeId];
    }
}
