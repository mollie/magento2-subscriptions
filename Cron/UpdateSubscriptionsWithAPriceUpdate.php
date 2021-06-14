<?php
/*
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Mollie\Subscriptions\Cron;


use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Framework\Pricing\PriceCurrencyInterface;
use Mollie\Payment\Config;
use Mollie\Payment\Helper\General;
use Mollie\Payment\Model\Mollie;
use Mollie\Subscriptions\Api\Data\SubscriptionToProductInterface;
use Mollie\Subscriptions\Api\SubscriptionToProductRepositoryInterface;

class UpdateSubscriptionsWithAPriceUpdate
{
    /**
     * @var Config
     */
    private $config;

    /**
     * @var Mollie
     */
    private $mollie;

    /**
     * @var General
     */
    private $mollieHelper;

    /**
     * @var SubscriptionToProductRepositoryInterface
     */
    private $subscriptionToProductRepository;

    /**
     * @var ProductRepositoryInterface
     */
    private $productRepository;

    private $apis = [];

    /**
     * @var PriceCurrencyInterface
     */
    private $priceCurrency;

    public function __construct(
        Config $config,
        Mollie $mollie,
        General $mollieHelper,
        SubscriptionToProductRepositoryInterface $subscriptionToProductRepository,
        ProductRepositoryInterface $productRepository,
        PriceCurrencyInterface $priceCurrency
    ) {
        $this->subscriptionToProductRepository = $subscriptionToProductRepository;
        $this->mollieHelper = $mollieHelper;
        $this->productRepository = $productRepository;
        $this->mollie = $mollie;
        $this->config = $config;
        $this->priceCurrency = $priceCurrency;
    }

    public function execute()
    {
        $subscriptions = $this->subscriptionToProductRepository->getSubscriptionsWithAPriceUpdate();

        foreach ($subscriptions->getItems() as $item) {
            try {
                $this->updateSubscription($item);
            } catch (\Exception $exception) {
                $this->config->addToLog('error', [
                    'message' => __('Unable to change the price for subscription "%1"', $item->getEntityId()),
                    'exception' => $exception->getMessage(),
                    'trace' => $exception->getTraceAsString(),
                ]);
            }
        }
    }

    private function getApiForStore(int $storeId)
    {
        if (array_key_exists($storeId, $this->apis)) {
            return $this->apis[$storeId];
        }

        $api = $this->mollie->getMollieApi($storeId);
        $this->apis[$storeId] = $api;

        return $api;
    }

    private function updateSubscription(SubscriptionToProductInterface $item)
    {
        $price = $this->productRepository->getById($item->getProductId())->getPrice();
        $api = $this->getApiForStore($item->getStoreId());

        $subscription = $api->subscriptions->getForId($item->getCustomerId(), $item->getSubscriptionId());
        $subscription->amount = $this->mollieHelper->getAmountArray(
            $subscription->amount->currency,
            $this->priceCurrency->convert($price, $item->getStoreId(), $subscription->amount->currency)
        );
        $subscription->update();

        $this->config->addToLog('success', __(
            'Updated subscription "%1" to price "%2"',
            $subscription->id,
            $price
        ));

        $item->setHasPriceUpdate(0);
        $this->subscriptionToProductRepository->save($item);
    }
}
