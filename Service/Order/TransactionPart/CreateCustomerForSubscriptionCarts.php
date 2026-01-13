<?php
/*
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Mollie\Subscriptions\Service\Order\TransactionPart;

use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\Sales\Api\Data\OrderInterface;
use Mollie\Payment\Model\Api;
use Mollie\Payment\Service\Order\TransactionPartInterface;
use Mollie\Subscriptions\Service\Order\OrderContainsSubscriptionProduct;

class CreateCustomerForSubscriptionCarts implements TransactionPartInterface
{
    public function __construct(
        private readonly Api $api,
        private readonly CustomerRepositoryInterface $customerRepository,
        private readonly OrderContainsSubscriptionProduct $orderContainsSubscriptionProduct
    ) {
    }

    public function process(OrderInterface $order, array $transaction): array
    {
        if (!$this->orderContainsSubscriptionProduct->check($order)) {
            return $transaction;
        }

        $transaction['customerId'] = $this->getCustomerId($order);

        return $transaction;
    }

    private function getCustomerId(OrderInterface $order): string
    {
        $customer = $this->customerRepository->getById($order->getCustomerId());
        $attribute = $customer->getExtensionAttributes()->getMollieCustomerId();

        if ($attribute) {
            return $attribute;
        }

        return $this->getCustomerIdFromMollie($order);
    }

    private function getCustomerIdFromMollie(OrderInterface $order): string
    {
        $this->api->load($order->getStoreId());
        $mollieCustomer = $this->api->customers->create([
            'name' => $order->getCustomerName(),
            'email' => $order->getCustomerEmail(),
        ]);

        $customer = $this->customerRepository->getById($order->getCustomerId());
        if ($customer->getExtensionAttributes()) {
            $customer->getExtensionAttributes()->setMollieCustomerId($mollieCustomer->id);
            $this->customerRepository->save($customer);
        }

        return $mollieCustomer->id;
    }
}
