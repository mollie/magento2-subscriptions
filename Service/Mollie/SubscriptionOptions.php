<?php
/*
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Mollie\Subscriptions\Service\Mollie;

use Magento\Framework\UrlInterface;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Api\Data\OrderItemInterface;
use Mollie\Payment\Helper\General;
use Mollie\Subscriptions\Config\Source\IntervalType;
use Mollie\Subscriptions\Config\Source\RepetitionType;
use Mollie\Subscriptions\DTO\SubscriptionOption;

class SubscriptionOptions
{
    /**
     * @var OrderInterface
     */
    private $order;

    /**
     * @var OrderItemInterface
     */
    private $orderItem;

    /**
     * @var array
     */
    private $options = [];

    /**
     * @var General
     */
    private $mollieHelper;

    /**
     * @var UrlInterface
     */
    private $urlBuilder;

    public function __construct(
        General $mollieHelper,
        UrlInterface $urlBuilder
    ) {
        $this->mollieHelper = $mollieHelper;
        $this->urlBuilder = $urlBuilder;
    }

    /**
     * @param OrderInterface $order
     * @return SubscriptionOption[]
     */
    public function forOrder(OrderInterface $order): array
    {
        $options = [];
        $this->order = $order;

        foreach ($order->getAllItems() as $orderItem) {
            if (!$orderItem->getProduct()->getData('mollie_subscription_product')) {
                continue;
            }

            $options[] = $this->createSubscriptionFor($orderItem);
        }

        return $options;
    }

    private function createSubscriptionFor(OrderItemInterface $orderItem): SubscriptionOption
    {
        $this->options = [];
        $this->orderItem = $orderItem;

        $this->addAmount();
        $this->addTimes();
        $this->addInterval();
        $this->addDescription();
        $this->addMetadata();
        $this->addWebhookUrl();

        return new SubscriptionOption(
            $orderItem->getProductId(),
            $this->order->getStoreId(),
            $this->options['amount'] ?? [],
            $this->options['interval'] ?? '',
            $this->options['description'] ?? '',
            $this->options['metadata'] ?? [],
            $this->options['webhookUrl'] ?? '',
            $this->options['times'] ?? null
        );
    }

    private function addAmount()
    {
        $this->options['amount'] = $this->mollieHelper->getAmountArray(
            $this->order->getOrderCurrencyCode(),
            $this->orderItem->getRowTotalInclTax()
        );
    }

    private function addTimes()
    {
        $product = $this->orderItem->getProduct();
        $type = $product->getData('mollie_subscription_repetition_type');
        if (!$type || $type == RepetitionType::INFINITE) {
            return;
        }

        $this->options['times'] = $product->getData('mollie_subscription_repetition_amount');
    }

    private function addInterval()
    {
        $product = $this->orderItem->getProduct();
        $intervalType = $product->getData('mollie_subscription_interval_type');
        $intervalAmount = (int)$product->getData('mollie_subscription_interval_amount');

        $this->options['interval'] = $intervalAmount . ' ' . $intervalType;
    }

    private function addDescription()
    {
        $product = $this->orderItem->getProduct();

        $this->options['description'] = $product->getName() . ' - ' . $this->getIntervalDescription();
    }

    private function addMetadata()
    {
        $product = $this->orderItem->getProduct();

        $this->options['metadata'] = ['sku' => $product->getSku()];
    }

    private function addWebhookUrl()
    {
        $this->options['webhookUrl'] = $this->urlBuilder->getUrl('mollie-subscriptions/api/webhook');
    }

    private function getIntervalDescription()
    {
        $product = $this->orderItem->getProduct();
        $intervalType = $product->getData('mollie_subscription_interval_type');
        $intervalAmount = (int)$product->getData('mollie_subscription_interval_amount');

        if ($intervalType == IntervalType::DAYS) {
            if ($intervalAmount == 1) {
                return __('Every day', $intervalAmount);
            }

            return __('Every %1 days', $intervalAmount);
        }

        if ($intervalType == IntervalType::WEEKS) {
            if ($intervalAmount == 1) {
                return __('Every week');
            }

            return __('Every %1 weeks', $intervalAmount);
        }

        if ($intervalType == IntervalType::MONTHS) {
            if ($intervalAmount == 1) {
                return __('Every month');
            }

            return __('Every %1 months', $intervalAmount);
        }

        return '';
    }
}
