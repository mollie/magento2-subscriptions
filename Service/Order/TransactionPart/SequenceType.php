<?php
/*
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Mollie\Subscriptions\Service\Order\TransactionPart;

use Magento\Sales\Api\Data\OrderInterface;
use Mollie\Payment\Config;
use Mollie\Payment\Model\Client\Orders;
use Mollie\Payment\Model\Client\Payments;
use Mollie\Payment\Service\Order\TransactionPartInterface;
use Mollie\Subscriptions\Service\Order\OrderContainsSubscriptionProduct;

class SequenceType implements TransactionPartInterface
{
    /**
     * @var Config
     */
    private $config;

    /**
     * @var OrderContainsSubscriptionProduct
     */
    private $orderContainsSubscriptionProduct;

    public function __construct(
        Config $config,
        OrderContainsSubscriptionProduct $orderContainsSubscriptionProduct
    ) {
        $this->config = $config;
        $this->orderContainsSubscriptionProduct = $orderContainsSubscriptionProduct;
    }

    public function process(OrderInterface $order, $apiMethod, array $transaction): array
    {
        if (!$this->orderContainsSubscriptionProduct->check($order)) {
            return $transaction;
        }

        if ($this->shouldBeRecurringSequence($order)) {
            return $this->setSequenceTypeRecurring($apiMethod, $transaction);
        }

        return $this->setSequenceTypeFirst($apiMethod, $transaction);
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

    protected function setSequenceTypeFirst(string $apiMethod, array $transaction): array
    {
        if ($apiMethod == Payments::CHECKOUT_TYPE) {
            $transaction['sequenceType'] = 'first';
        }

        if ($apiMethod == Orders::CHECKOUT_TYPE) {
            $transaction['payment']['sequenceType'] = 'first';
        }

        return $transaction;
    }

    protected function setSequenceTypeRecurring(string $apiMethod, array $transaction): array
    {
        if ($apiMethod == Payments::CHECKOUT_TYPE) {
            $transaction['sequenceType'] = 'recurring';
        }

        if ($apiMethod == Orders::CHECKOUT_TYPE) {
            $transaction['payment']['sequenceType'] = 'recurring';
        }

        return $transaction;
    }
}
