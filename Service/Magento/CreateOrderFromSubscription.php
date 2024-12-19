<?php

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

class CreateOrderFromSubscription
{
    /**
     * @var MollieApiClient
     */
    private $api;
    /**
     * @var Subscription
     */
    private $subscription;
    /**
     * @var CustomerInterface
     */
    private $customer;
    /**
     * @var Config
     */
    private $config;
    /**
     * @var MollieCustomerRepositoryInterface
     */
    private $mollieCustomerRepository;
    /**
     * @var CustomerRepositoryInterface
     */
    private $customerRepository;
    /**
     * @var AddressRepositoryInterface
     */
    private $addressRepository;
    /**
     * @var CartRepositoryInterface
     */
    private $cartRepository;
    /**
     * @var CartManagementInterface
     */
    private $cartManagement;
    /**
     * @var OrderRepositoryInterface
     */
    private $orderRepository;
    /**
     * @var ProductRepositoryInterface
     */
    private $productRepository;
    /**
     * @var AddressInterfaceFactory
     */
    private $addressFactory;
    /**
     * @var MollieLogger
     */
    private $mollieLogger;
    /**
     * @var OrderAddressRepositoryInterface
     */
    private $orderAddressRepository;

    /**
     * @var ProductInterface
     */
    private $product;
    /**
     * @var SubscriptionAddProductToCart
     */
    private $subscriptionAddToCart;

    public function __construct(
        Config $config,
        MollieCustomerRepositoryInterface $mollieCustomerRepository,
        CustomerRepositoryInterface $customerRepository,
        AddressRepositoryInterface $addressRepository,
        CartRepositoryInterface $cartRepository,
        CartManagementInterface $cartManagement,
        OrderRepositoryInterface $orderRepository,
        ProductRepositoryInterface $productRepository,
        AddressInterfaceFactory $addressFactory,
        MollieLogger $mollieLogger,
        OrderAddressRepositoryInterface $orderAddressRepository,
        SubscriptionAddProductToCart $addProductToCart
    ) {
        $this->mollieCustomerRepository = $mollieCustomerRepository;
        $this->customerRepository = $customerRepository;
        $this->addressRepository = $addressRepository;
        $this->cartRepository = $cartRepository;
        $this->cartManagement = $cartManagement;
        $this->orderRepository = $orderRepository;
        $this->productRepository = $productRepository;
        $this->addressFactory = $addressFactory;
        $this->config = $config;
        $this->mollieLogger = $mollieLogger;
        $this->orderAddressRepository = $orderAddressRepository;
        $this->subscriptionAddToCart = $addProductToCart;
    }

    public function execute(MollieApiClient $api, Payment $molliePayment, Subscription $subscription): OrderInterface
    {
        $this->api = $api;
        $this->subscription = $subscription;

        $mollieCustomer = $this->mollieCustomerRepository->getByMollieCustomerId($molliePayment->customerId);
        if (!$mollieCustomer) {
            throw new \Exception(
                'Mollie customer with ID ' . $molliePayment->customerId . ' not found in database'
            );
        }

        $this->customer = $this->customerRepository->getById($mollieCustomer->getCustomerId());

        $cart = $this->getCart();
        $this->product = $this->subscriptionAddToCart->execute($cart, $this->subscription->metadata);

        $cart->setBillingAddress($this->formatAddress($this->getAddress('billing')));

        if (!$this->product->getIsVirtual()) {
            $this->setShippingAddress($cart);
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
        $cart->setStoreId($this->customer->getStoreId());
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

    private function setShippingAddress(CartInterface $cart)
    {
        $shippingAddress = $this->formatAddress($this->getAddress('shipping'));
        $cart->setShippingAddress($shippingAddress);

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
    }

    /**
     * @return CustomerAddressInterface|OrderAddressInterface
     */
    private function getAddress(string $type)
    {
        if (isset($this->subscription->metadata->{$type . 'AddressId'})) {
            $id = $this->subscription->metadata->{$type . 'AddressId'};

            return $this->orderAddressRepository->get($id);
        }

        return $this->addressRepository->getById($this->customer->getDefaultBilling());
    }
}
