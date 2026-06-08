<?php
/*
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Mollie\Subscriptions\Cron;


use Exception;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Framework\Pricing\PriceCurrencyInterface;
use Mollie\Payment\Helper\General;
use Mollie\Subscriptions\Api\Data\SubscriptionToProductInterface;
use Mollie\Subscriptions\Api\SubscriptionToProductRepositoryInterface;
use Mollie\Subscriptions\Config;
use Mollie\Subscriptions\Service\Magento\GetPriceUpdateForSubscription;
use Mollie\Subscriptions\Service\Mollie\MollieSubscriptionApi;

class UpdateSubscriptionsWithAPriceUpdate
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
    /**
     * @var GetPriceUpdateForSubscription
     */
    private $getPriceUpdateForSubscription;

    public function __construct(
        Config $config,
        MollieSubscriptionApi $mollieSubscriptionApi,
        General $mollieHelper,
        SubscriptionToProductRepositoryInterface $subscriptionToProductRepository,
        ProductRepositoryInterface $productRepository,
        PriceCurrencyInterface $priceCurrency,
        GetPriceUpdateForSubscription $getPriceUpdateForSubscription
    )
    {
        $this->config = $config;
        $this->mollieSubscriptionApi = $mollieSubscriptionApi;
        $this->mollieHelper = $mollieHelper;
        $this->subscriptionToProductRepository = $subscriptionToProductRepository;
        $this->productRepository = $productRepository;
        $this->priceCurrency = $priceCurrency;
        $this->getPriceUpdateForSubscription = $getPriceUpdateForSubscription;
    }

    public function execute()
    {
        $subscriptions = $this->subscriptionToProductRepository->getSubscriptionsWithAPriceUpdate();

        foreach ($subscriptions->getItems() as $item) {
            if (!$this->config->updateSubscriptionWhenPriceChanges($item->getStoreId())) {
                continue;
            }

            try {
                $this->updateSubscription($item);
            } catch (Exception $exception) {
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

        $api = $this->mollieSubscriptionApi->loadByStore($storeId);
        $this->apis[$storeId] = $api;

        return $api;
    }

    private function updateSubscription(SubscriptionToProductInterface $item): void
    {
        $product = $this->productRepository->getById($item->getProductId());
        $price = $this->getPriceUpdateForSubscription->execute($item, $product);
        $api = $this->getApiForStore($item->getStoreId());

        $subscription = $api->subscriptions->getForId($item->getCustomerId(), $item->getSubscriptionId());

        if ($subscription->status == 'canceled') {
            $this->subscriptionToProductRepository->delete($item);

            $this->config->addToLog('success', __(
                'Subscription "%1" canceled, deleted database record with ID "%2"',
                $subscription->id,
                $item->getEntityId()
            ));

            return;
        }

        $amount = $this->mollieHelper->getAmountArray(
            $subscription->amount->currency,
            $this->priceCurrency->convert($price, $item->getStoreId(), $subscription->amount->currency)
        );

        if ($subscription->amount->currency == $amount['currency'] &&
            $subscription->amount->value == $amount['value']
        ) {
            $item->setHasPriceUpdate(0);
            $this->subscriptionToProductRepository->save($item);

            return;
        }

        $subscription->amount = $amount;
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
