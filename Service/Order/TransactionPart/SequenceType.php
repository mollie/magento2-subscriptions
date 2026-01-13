<?php
/*
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Mollie\Subscriptions\Service\Order\TransactionPart;

use Magento\Sales\Api\Data\OrderInterface;
use Mollie\Payment\Config;
use Mollie\Payment\Service\Order\TransactionPartInterface;
use Mollie\Subscriptions\Service\Order\OrderContainsSubscriptionProduct;

class SequenceType implements TransactionPartInterface
{
    public function __construct(
        private readonly Config $config,
        private readonly OrderContainsSubscriptionProduct $orderContainsSubscriptionProduct
    ) {}

    public function process(OrderInterface $order, array $transaction): array
    {
        if (!$this->orderContainsSubscriptionProduct->check($order)) {
            return $transaction;
        }

        if ($this->shouldBeRecurringSequence($order)) {
            $transaction['sequenceType'] = 'recurring';

            return $transaction;
        }

        $transaction['sequenceType'] = 'first';

        return $transaction;
    }

    private function shouldBeRecurringSequence(OrderInterface $order): bool
    {
        if (
            !$this->config->isMagentoVaultEnabled($order->getStoreId()) ||
            !$order->getPayment() ||
            !$order->getPayment()->getExtensionAttributes() ||
            !$order->getPayment()->getExtensionAttributes()->getVaultPaymentToken()
        ) {
            return false;
        }

        $paymentToken = $order->getPayment()->getExtensionAttributes()->getVaultPaymentToken();
        if ($order->getPayment()->getMethod() != $paymentToken->getPaymentMethodCode()) {
            return false;
        }

        return true;
    }
}
