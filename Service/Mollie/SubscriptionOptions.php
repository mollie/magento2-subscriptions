<?php
/*
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Mollie\Subscriptions\Service\Mollie;

use Magento\Framework\UrlInterface;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Api\Data\OrderItemInterface;
use Magento\Store\Model\StoreManagerInterface;
use Mollie\Payment\Helper\General;
use Mollie\Subscriptions\Config\Source\IntervalType;
use Mollie\Subscriptions\Config\Source\RepetitionType;
use Mollie\Subscriptions\DTO\ProductSubscriptionOption;
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

    /**
     * @var ParseSubscriptionOptions
     */
    private $parseSubscriptionOptions;

    /**
     * @var ProductSubscriptionOption
     */
    private $currentOption;
    /**
     * @var StoreManagerInterface
     */
    private $storeManager;

    /**
     * @var GetShippingCostForOrderItem
     */
    private $getShippingCostForOrderItem;

    public function __construct(
        General $mollieHelper,
        UrlInterface $urlBuilder,
        ParseSubscriptionOptions $parseSubscriptionOptions,
        GetShippingCostForOrderItem $getShippingCostForOrderItem,
        StoreManagerInterface $storeManager
    ) {
        $this->mollieHelper = $mollieHelper;
        $this->urlBuilder = $urlBuilder;
        $this->parseSubscriptionOptions = $parseSubscriptionOptions;
        $this->getShippingCostForOrderItem = $getShippingCostForOrderItem;
        $this->storeManager = $storeManager;
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
        $this->loadSubscriptionOption($orderItem);

        $this->addAmount();
        $this->addShippingCost();
        $this->addTimes();
        $this->addInterval();
        $this->addDescription();
        $this->addMetadata();
        $this->addWebhookUrl();
        $this->addStartDate();

        $amount = $this->mollieHelper->getAmountArray(
            $this->order->getOrderCurrencyCode(),
            $this->options['amount']
        );

        return new SubscriptionOption(
            $orderItem->getProductId(),
            $this->order->getStoreId(),
            $amount,
            $this->options['interval'] ?? '',
            $this->options['description'] ?? '',
            $this->options['metadata'] ?? [],
            $this->options['webhookUrl'] ?? '',
            $this->options['startDate'],
            $this->options['times'] ?? null
        );
    }

    private function addAmount(): void
    {
        $rowTotal = $this->orderItem->getRowTotalInclTax();
        if (!$rowTotal && $this->orderItem->getParentItem()) {
            $rowTotal = $this->orderItem->getParentItem()->getRowTotalInclTax();
        }

        $this->options['amount'] = $rowTotal;
    }

    private function addShippingCost(): void
    {
        if ($this->orderItem->getIsVirtual()) {
            return;
        }

        $shippingCost = $this->getShippingCostForOrderItem->execute($this->order, $this->orderItem);
        $this->options['amount'] += $shippingCost;
    }

    private function addTimes(): void
    {
        if ($this->currentOption->getRepetitionType() == RepetitionType::INFINITE) {
            return;
        }

        $this->options['times'] = (int)$this->currentOption->getRepetitionAmount();
    }

    private function addInterval(): void
    {
        $intervalType = $this->currentOption->getIntervalType();
        $intervalAmount = $this->currentOption->getIntervalAmount();

        $this->options['interval'] = (int)$intervalAmount . ' ' . $intervalType;
    }

    private function addDescription(): void
    {
        $product = $this->orderItem->getProduct();

        $this->options['description'] = $product->getName() . ' - ' . $this->getIntervalDescription();
    }

    private function addMetadata(): void
    {
        $product = $this->orderItem->getProduct();
        $metadata = [
            'sku' => $product->getSku(),
            'quantity' => $this->orderItem->getQtyOrdered(),
            'billingAddressId' => $this->order->getBillingAddressId(),
        ];

        if ($parent = $this->orderItem->getParentItem()) {
            $metadata['parent_sku'] = $parent->getProduct()->getSku();
        }

        $this->options['metadata'] = $metadata;

        if (!$this->orderItem->getIsVirtual()) {
            $this->options['metadata']['shippingAddressId'] = $this->order->getshippingAddressId();
        }
    }

    private function addWebhookUrl(): void
    {
        $this->options['webhookUrl'] = $this->urlBuilder->getUrl(
            'mollie-subscriptions/api/webhook',
            ['___store' => $this->storeManager->getStore($this->order->getStoreId())->getCode()]
        );
    }

    private function addStartDate(): void
    {
        $now = new \DateTimeImmutable();

        $this->options['startDate'] = $now->add(new \DateInterval('P' . $this->getDateInterval()));
    }

    /**
     * Examples:
     * 7D (7 days)
     * 2W (2 weeks)
     * 3M (3 months)
     *
     * @return string
     */
    private function getDateInterval(): string
    {
        $interval = $this->currentOption->getIntervalType();
        $intervalAmount = (int)$this->currentOption->getIntervalAmount();

        if ($interval == IntervalType::DAYS) {
            return $intervalAmount . 'D';
        }

        if ($interval == IntervalType::WEEKS) {
            return $intervalAmount . 'W';
        }

        return $intervalAmount . 'M';
    }

    private function getIntervalDescription(): string
    {
        $intervalType = $this->currentOption->getIntervalType();
        $intervalAmount = (int)$this->currentOption->getIntervalAmount();

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

    private function loadSubscriptionOption(OrderItemInterface $item): void
    {
        $mollieMetadata = $item->getBuyRequest()->getData('mollie_metadata');
        if ($mollieMetadata === null) {
            throw new \Exception('No Mollie Metadata present on order item');
        }

        if (!isset($mollieMetadata['recurring_metadata'], $mollieMetadata['recurring_metadata']['option_id'])) {
            throw new \Exception('No recurring metadata or option_id present on order item');
        }

        $optionId = $mollieMetadata['recurring_metadata']['option_id'];
        $options = $this->parseSubscriptionOptions->execute($item->getProduct());
        foreach($options as $option) {
            if ($option->getIdentifier() == $optionId) {
                $this->currentOption = $option;
                return;
            }
        }

        throw new \Exception(sprintf('No option with ID %s available', $optionId));
    }
}
