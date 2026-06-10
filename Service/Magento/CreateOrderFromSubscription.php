<?php
/*
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Mollie\Subscriptions\Service\Magento;

use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Customer\Api\AddressRepositoryInterface;
use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\Customer\Api\Data\AddressInterface as CustomerAddressInterface;
use Magento\Customer\Api\Data\CustomerInterface;
use Magento\Quote\Api\CartManagementInterface;
use Magento\Quote\Api\CartRepositoryInterface;
use Magento\Quote\Api\Data\AddressInterface;
use Magento\Quote\Api\Data\AddressInterfaceFactory;
use Magento\Quote\Api\Data\CartInterface;
use Magento\Quote\Model\Quote\Address as QuoteAddress;
use Magento\Quote\Model\Quote\Address\RateFactory;
use Magento\Sales\Api\Data\OrderAddressInterface;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Api\OrderAddressRepositoryInterface;
use Magento\Sales\Api\OrderRepositoryInterface;
use Mollie\Api\MollieApiClient;
use Mollie\Api\Resources\Payment;
use Mollie\Api\Resources\Subscription;
use Mollie\Payment\Api\MollieCustomerRepositoryInterface;
use Mollie\Payment\Logger\MollieLogger;
use Mollie\Subscriptions\Config;
use Mollie\Subscriptions\Model\Carrier\SubscriptionShipping;

class CreateOrderFromSubscription
{
    /**
     * @var Subscription
     */
    private $subscription;
    /**
     * @var CustomerInterface
     */
    private $customer;

    /**
     * @var ProductInterface
     */
    private $product;

    public function __construct(
        private readonly Config $config,
        private readonly MollieCustomerRepositoryInterface $mollieCustomerRepository,
        private readonly CustomerRepositoryInterface $customerRepository,
        private readonly AddressRepositoryInterface $addressRepository,
        private readonly CartRepositoryInterface $cartRepository,
        private readonly CartManagementInterface $cartManagement,
        private readonly OrderRepositoryInterface $orderRepository,
        private readonly ProductRepositoryInterface $productRepository,
        private readonly AddressInterfaceFactory $addressFactory,
        private readonly MollieLogger $mollieLogger,
        private readonly OrderAddressRepositoryInterface $orderAddressRepository,
        private readonly SubscriptionAddProductToCart $subscriptionAddToCart,
        private readonly RateFactory $rateFactory
    ) {
    }

    public function execute(MollieApiClient $api, Payment $molliePayment, Subscription $subscription): OrderInterface
    {
        $this->subscription = $subscription;

        $mollieCustomer = $this->mollieCustomerRepository->getByMollieCustomerId($molliePayment->customerId);
        if (!$mollieCustomer) {
            throw new \Exception(
                'Mollie customer with ID ' . $molliePayment->customerId . ' not found in database'
            );
        }

        $this->customer = $this->customerRepository->getById($mollieCustomer->getCustomerId());

        $cart = $this->getCart();
        if (!$this->subscriptionProductIsVirtual()) {
            $cart->setShippingAddress($this->formatAddress($this->getAddress('shipping')));
        }

        $cart->setBillingAddress($this->formatAddress($this->getAddress('billing')));
        $this->product = $this->subscriptionAddToCart->execute($cart, $this->subscription);

        if (!$this->product->getIsVirtual()) {
            $this->configureShipping($cart);
        }

        $cart->getPayment()->addData(['method' => 'mollie_methods_' . $molliePayment->method]);

        $cart->collectTotals();
        $this->cartRepository->save($cart);

        $order = $this->cartManagement->submit($cart);
        $order->setMollieTransactionId($molliePayment->id);
        $order->getPayment()->setAdditionalInformation('subscription_created', $subscription->createdAt);
        $this->orderRepository->save($order);

        return $order;
    }

    private function getCart(): CartInterface
    {
        $cartId = $this->cartManagement->createEmptyCart();
        $cart = $this->cartRepository->get($cartId);
        $cart->setStoreId(storeId($this->customer->getStoreId()));
        $cart->setCustomer($this->customer);
        $cart->setCustomerIsGuest(0);

        return $cart;
    }

    /**
     * @param CustomerAddressInterface|OrderAddressInterface $address
     * @return AddressInterface
     */
    private function formatAddress($address): AddressInterface
    {
        $quoteAddress = $this->addressFactory->create();
        $quoteAddress->setFirstname($address->getFirstName());
        $quoteAddress->setMiddlename($address->getMiddlename());
        $quoteAddress->setLastname($address->getLastname());
        $quoteAddress->setStreet($address->getStreet());
        $quoteAddress->setPostcode($address->getPostcode());
        $quoteAddress->setCity($address->getCity());
        $quoteAddress->setCountryId($address->getCountryId());
        $quoteAddress->setCompany($address->getCompany());
        $quoteAddress->setTelephone($address->getTelephone());
        $quoteAddress->setFax($address->getFax());
        $quoteAddress->setVatId($address->getVatId());
        $quoteAddress->setSuffix($address->getSuffix());
        $quoteAddress->setPrefix($address->getPrefix());
        $quoteAddress->setRegionId($address->getRegionId());
        $quoteAddress->setCustomAttributes($address->getCustomAttributes());

        return $quoteAddress;
    }

    private function configureShipping(CartInterface $cart): void
    {
        $shippingAddress = $cart->getShippingAddress();
        $shippingAddress->setCollectShippingRates(true);
        $shippingAddress->collectShippingRates();
        $shippingAddress->setShippingMethod($this->config->getShippingMethod());

        // There are no rates available. Switch to the first available shipping method.
        if ($shippingAddress->getShippingRateByCode($this->config->getShippingMethod()) === false &&
            count($shippingAddress->getShippingRatesCollection()->getItems()) > 0
        ) {
            $newMethod = $shippingAddress->getShippingRatesCollection()->getFirstItem()->getCode();
            $shippingAddress->setShippingMethod($newMethod);

            $this->mollieLogger->addInfoLog(
                'subscriptions',
                'No rates available for ' . $this->config->getShippingMethod() .
                ', switched to ' . $newMethod
            );
        }

        if ($shippingAddress->getShippingRateByCode($shippingAddress->getShippingMethod()) === false) {
            $this->applyFallbackShippingMethod($shippingAddress);
        }
    }

    private function applyFallbackShippingMethod(QuoteAddress $shippingAddress): void
    {
        $fallbackCode = 'mollie_subscriptions_fallback_shipping';

        $rate = $this->rateFactory->create();
        $rate->setCarrier(SubscriptionShipping::CARRIER_CODE);
        $rate->setCarrierTitle('Mollie Subscriptions Shipping Fallback');
        $rate->setMethod('fallback_shipping');
        $rate->setMethodTitle('Subscription Shipping');
        $rate->setPrice(0);
        $rate->setCost(0);
        $rate->setCode($fallbackCode);

        $shippingAddress->addShippingRate($rate);
        $shippingAddress->setShippingMethod($fallbackCode);

        $this->mollieLogger->addInfoLog(
            'subscriptions',
            'No shipping rates available at all, using Mollie Subscriptions fallback carrier'
        );
    }

    private function getAddress(string $type): CustomerAddressInterface|OrderAddressInterface
    {
        if (isset($this->subscription->metadata->{$type . 'AddressId'})) {
            $id = $this->subscription->metadata->{$type . 'AddressId'};

            return $this->orderAddressRepository->get($id);
        }

        return $this->addressRepository->getById($this->customer->getDefaultBilling());
    }

    private function subscriptionProductIsVirtual(): bool
    {
        $metadata = $this->subscription->metadata;
        $sku = $metadata->sku;
        $parentSku = isset($metadata->parent_sku) ? $metadata->parent_sku : null;

        return $this->productRepository->get($parentSku ?: $sku)->getIsVirtual();
    }
}
