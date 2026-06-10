<?php

declare(strict_types=1);

namespace Mollie\Subscriptions\Service\Mollie;

use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Api\Data\OrderItemInterface;
use Mollie\Subscriptions\DTO\ProductSubscriptionOption;

class OrderHasTrialProduct
{
    public function __construct(
        private readonly OrderHasTrialProductResultFactory $resultFactory,
        private readonly GetSelectedOption $getSelectedOption
    ) {
    }

    public function execute(OrderInterface $order): OrderHasTrialProductResult
    {
        $trialAmountTotal = 0;
        $hasTrialProduct = false;
        foreach ($order->getItems() as $orderItem) {
            if (!$orderItem->getProduct()->getData('mollie_subscription_product')) {
                continue;
            }

            $selectedOption = $this->loadSubscriptionOption($orderItem);
            if ($selectedOption === null || !$selectedOption->getTrialDays()) {
                continue;
            }

            $hasTrialProduct = true;
            $trialAmountTotal += $orderItem->getRowTotalInclTax();
        }

        if (!$hasTrialProduct) {
            return $this->resultFactory->create([
                'outcome' => false,
                'trialAmountTotal' => $trialAmountTotal,
            ]);
        }

        $trialAmountTotal += $order->getShippingInclTax();
        $trialAmountTotal += $order->getDiscountAmount();

        $orderTotal = $order->getGrandTotal();
        if ($orderTotal - $trialAmountTotal <= 0) {
            $trialAmountTotal -= 0.01;
        }

        return $this->resultFactory->create([
            'outcome' => true,
            'trialAmountTotal' => $trialAmountTotal,
        ]);
    }

    private function loadSubscriptionOption(OrderItemInterface $item): ProductSubscriptionOption
    {
        $mollieMetadata = $item->getBuyRequest()->getData('mollie_metadata');
        if ($mollieMetadata === null) {
            throw new \Exception('No Mollie Metadata present on order item');
        }

        if (!isset($mollieMetadata['recurring_metadata'], $mollieMetadata['recurring_metadata']['option_id'])) {
            throw new \Exception('No recurring metadata or option_id present on order item');
        }

        return $this->getSelectedOption->execute(
            $item->getProduct(),
            $mollieMetadata['recurring_metadata']['option_id']
        );
    }
}
