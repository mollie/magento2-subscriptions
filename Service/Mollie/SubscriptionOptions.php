<?php
/*
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Mollie\Subscriptions\Service\Mollie;

use Magento\Framework\Event\ManagerInterface;
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

    /**
     * @var ManagerInterface
     */
    private $eventManager;

    public function __construct(
        General $mollieHelper,
        UrlInterface $urlBuilder,
        ParseSubscriptionOptions $parseSubscriptionOptions,
        GetShippingCostForOrderItem $getShippingCostForOrderItem,
        StoreManagerInterface $storeManager,
        ManagerInterface $eventManager
    ) {
        $this->mollieHelper = $mollieHelper;
        $this->urlBuilder = $urlBuilder;
        $this->parseSubscriptionOptions = $parseSubscriptionOptions;
        $this->getShippingCostForOrderItem = $getShippingCostForOrderItem;
        $this->storeManager = $storeManager;
        $this->eventManager = $eventManager;
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
        $this->loadSubscriptionOption();

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

        $subscriptionOption = new SubscriptionOption(
            $orderItem->getProductId(),
            $this->currentOption->getIdentifier(),
            $this->order->getStoreId(),
            $amount,
            $this->options['interval'] ?? '',
            $this->options['description'] ?? '',
            $this->options['metadata'] ?? [],
            $this->options['webhookUrl'] ?? '',
            $this->options['startDate'],
            $this->options['times'] ?? null
        );

        $subscriptionOption->setOrderItem($orderItem);

        $this->eventManager->dispatch('mollie_subscription_option_init', [
            'dto' => $subscriptionOption
        ]);

        return $subscriptionOption;
    }

    private function addAmount(): void
    {
        if ($this->currentOption->getPrice() !== null) {
            $this->options['amount'] = $this->currentOption->getPrice();
            return;
        }

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

        $interval = (int)$intervalAmount . ' ' . $intervalType;
        if ($intervalType == IntervalType::YEARS) {
            $interval = '365 days';
        }

        $this->options['interval'] = $interval;
    }

    private function addDescription(): void
    {
        $product = $this->orderItem->getProduct();

        $this->options['description'] = $product->getName() . ' - ' . $this->getIntervalDescription();
    }

    private function addMetadata(): void
    {
        $product = $this->orderItem->getProduct();
        $optionId = $this->getOptionIdFromOrderItem();

        $metadata = [
            'sku' => $product->getSku(),
            'quantity' => $this->orderItem->getQtyOrdered(),
            'billingAddressId' => $this->order->getBillingAddressId(),
            'optionId' => $optionId,
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
            ['___store' => $this->storeManager->getStore($this->order->getStoreId())->getId()]
        );
    }

    private function addStartDate(): void
    {
        $now = new \DateTimeImmutable();
        $startDate = $now->add(new \DateInterval('P' . $this->getDateInterval()));

        if ($days = $this->currentOption->getTrialDays()) {
            $startDate = $startDate->add(new \DateInterval('P' . $days . 'D'));
        }

        $this->options['startDate'] = $startDate;
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

        if ($interval == IntervalType::YEARS) {
            return '365D';
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

        // 365 days is the maximum.
        if ($intervalType == IntervalType::YEARS) {
            return __('Every year');
        }

        return '';
    }

    private function getOptionIdFromOrderItem(): string
    {
        $mollieMetadata = $this->orderItem->getBuyRequest()->getData('mollie_metadata');
        if ($mollieMetadata === null) {
            throw new \Exception('No Mollie Metadata present on order item');
        }

        if (!isset($mollieMetadata['recurring_metadata'], $mollieMetadata['recurring_metadata']['option_id'])) {
            throw new \Exception('No recurring metadata or option_id present on order item');
        }

        return $mollieMetadata['recurring_metadata']['option_id'];
    }

    private function loadSubscriptionOption(): void
    {
        $optionId = $this->getOptionIdFromOrderItem();
        $options = $this->parseSubscriptionOptions->execute($this->orderItem->getProduct());
        foreach($options as $option) {
            if ($option->getIdentifier() == $optionId) {
                $this->currentOption = $option;
                return;
            }
        }

        throw new \Exception(sprintf('No option with ID %s available', $optionId));
    }
}
