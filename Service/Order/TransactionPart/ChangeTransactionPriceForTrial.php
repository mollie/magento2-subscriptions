<?php

declare(strict_types=1);

namespace Mollie\Subscriptions\Service\Order\TransactionPart;

use Magento\Sales\Api\Data\OrderInterface;
use Mollie\Payment\Helper\General;
use Mollie\Payment\Model\Client\Payments;
use Mollie\Payment\Service\Order\TransactionPartInterface;
use Mollie\Subscriptions\Service\Mollie\OrderHasTrialProduct;

class ChangeTransactionPriceForTrial implements TransactionPartInterface
{
    public function __construct(
        private readonly General $mollieHelper,
        private readonly OrderHasTrialProduct $orderHasTrialProduct
    ) {}

    public function process(OrderInterface $order, array $transaction): array
    {
        $result = $this->orderHasTrialProduct->execute($order);
        if (!$result->getOutcome()) {
            return $transaction;
        }

        // Change amount
        $originalAmount = (float)$transaction['amount']['value'];
        $transaction['amount'] = $this->mollieHelper->getAmountArray(
            $transaction['amount']['currency'],
            $originalAmount - $result->getTrialAmountTotal(),
        );

        return $transaction;
    }
}
