<?php

namespace Mollie\Subscriptions\Observer\CustomerLogin;

use Magento\Customer\Model\Customer;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\Message\ManagerInterface;
use Magento\Quote\Api\CartRepositoryInterface;
use Magento\Quote\Api\Data\CartInterface;
use Mollie\Subscriptions\Service\Cart\CustomerAlreadyHasSubscriptionToProduct;

class PreventDuplicateSubscriptionProductsInCart implements ObserverInterface
{
    /**
     * @var CartInterface
     */
    private $cartRepository;

    /**
     * @var ManagerInterface
     */
    private $messageManager;

    /**
     * @var CustomerAlreadyHasSubscriptionToProduct
     */
    private $customerAlreadyHasSubscriptionToProduct;

    /**
     * @var bool
     */
    private $itemRemoved = false;

    public function __construct(
        CartRepositoryInterface $cartRepository,
        ManagerInterface $messageManager,
        CustomerAlreadyHasSubscriptionToProduct $customerAlreadyHasSubscriptionToProduct
    ) {
        $this->cartRepository = $cartRepository;
        $this->messageManager = $messageManager;
        $this->customerAlreadyHasSubscriptionToProduct = $customerAlreadyHasSubscriptionToProduct;
    }

    public function execute(Observer $observer)
    {
        /** @var Customer $customer */
        $customer = $observer->getData('customer');

        $cart = $this->cartRepository->getForCustomer($customer->getId());
        $items = $cart->getItems();
        if (!$items) {
            return;
        }

        $this->itemRemoved = false;
        foreach ($items as $item) {
            if ($this->customerAlreadyHasSubscriptionToProduct->execute($customer, $item->getProduct())) {
                $this->removeItem($item, $cart);
                $this->itemRemoved = true;
            }
        }

        if ($this->itemRemoved) {
            $this->cartRepository->save($cart);
        }
    }

    /**
     * @param \Magento\Quote\Api\Data\CartItemInterface $item
     * @param CartInterface $cart
     * @return void
     */
    public function removeItem(\Magento\Quote\Api\Data\CartItemInterface $item, CartInterface $cart): void
    {
        $productName = $item->getProduct()->getName();
        $cart->removeItem($item->getItemId());

        if (!$this->itemRemoved) {
            $this->messageManager->addWarningMessage(__(
                'You already have a subscription to "%1". This product has been removed from your cart.',
                $productName
            ));
        }
    }
}
