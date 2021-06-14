<?php
/*
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Mollie\Subscriptions\Service\Order\TransactionPart;

use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\Sales\Api\Data\OrderInterface;
use Mollie\Payment\Config;
use Mollie\Payment\Model\Api;
use Mollie\Payment\Model\Client\Orders;
use Mollie\Payment\Model\Client\Payments;
use Mollie\Payment\Service\Order\TransactionPartInterface;
use Mollie\Subscriptions\Service\Order\OrderContainsSubscriptionProduct;

class CreateCustomerForSubscriptionCarts implements TransactionPartInterface
{
    /**
     * @var Api
     */
    private $api;

    /**
     * @var CustomerRepositoryInterface
     */
    private $customerRepository;

    /**
     * @var Config
     */
    private $config;

    /**
     * @var OrderContainsSubscriptionProduct
     */
    private $orderContainsSubscriptionProduct;

    public function __construct(
        Api $api,
        CustomerRepositoryInterface $customerRepository,
        Config $config,
        OrderContainsSubscriptionProduct $orderContainsSubscriptionProduct
    ) {
        $this->api = $api;
        $this->customerRepository = $customerRepository;
        $this->config = $config;
        $this->orderContainsSubscriptionProduct = $orderContainsSubscriptionProduct;
    }

    public function process(OrderInterface $order, $apiMethod, array $transaction)
    {
        if (!$this->orderContainsSubscriptionProduct->check($order)) {
            return $transaction;
        }

        if ($apiMethod == Payments::CHECKOUT_TYPE) {
            $transaction['customerId'] = $this->getCustomerId($order);
        }

        if ($apiMethod == Orders::CHECKOUT_TYPE) {
            $transaction['payment']['customerId'] = $this->getCustomerId($order);
        }

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
