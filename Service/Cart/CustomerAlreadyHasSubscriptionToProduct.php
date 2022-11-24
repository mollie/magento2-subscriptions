<?php

namespace Mollie\Subscriptions\Service\Cart;

use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\Customer\Api\Data\CustomerInterface;
use Magento\Framework\Exception\NotFoundException;
use Mollie\Subscriptions\Api\SubscriptionToProductRepositoryInterface;

class CustomerAlreadyHasSubscriptionToProduct
{
    /**
     * @var CustomerRepositoryInterface
     */
    private $customerRepository;

    /**
     * @var SubscriptionToProductRepositoryInterface
     */
    private $subscriptionToProductRepository;

    public function __construct(
        CustomerRepositoryInterface $customerRepository,
        SubscriptionToProductRepositoryInterface $subscriptionToProductRepository
    ) {
        $this->subscriptionToProductRepository = $subscriptionToProductRepository;
        $this->customerRepository = $customerRepository;
    }

    public function execute(CustomerInterface $customer, ProductInterface $product): bool
    {
        try {
            $customerData = $this->customerRepository->getById($customer->getId());
            $mollieCustomerId = $customerData->getExtensionAttributes()->getMollieCustomerId();

            if (!$mollieCustomerId) {
                return false;
            }

            $this->subscriptionToProductRepository->getByCustomerIdAndProductId($mollieCustomerId, $product->getId());
            return true;
        } catch (NotFoundException $e) {
            return false;
        }
    }
}
