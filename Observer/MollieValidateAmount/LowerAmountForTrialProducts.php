<?php

declare(strict_types=1);

namespace Mollie\Subscriptions\Observer\MollieValidateAmount;

use Magento\Framework\DataObject;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Sales\Api\Data\OrderInterface;
use Mollie\Subscriptions\Service\Mollie\OrderHasTrialProduct;

class LowerAmountForTrialProducts implements ObserverInterface
{
    public function __construct(
        private readonly OrderHasTrialProduct $orderHasTrialProduct
    ) {
    }

    public function execute(Observer $observer): void
    {
        /** @var DataObject $data */
        $data = $observer->getData('info');
        /** @var OrderInterface $order */
        $order = $data->getData('order');

        $result = $this->orderHasTrialProduct->execute($order);
        if (!$result->getOutcome()) {
            return;
        }

        $amount = $data->getData('amount') + $result->getTrialAmountTotal();
        $orderAmount = $data->getData('orderAmount');

        $data->setData('result', abs($amount - $orderAmount) < 0.01);
    }
}
