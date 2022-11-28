<?php

namespace Mollie\Subscriptions\Plugin\Checkout\Model;

use Magento\Checkout\Model\Session;
use Magento\Customer\Model\Customer;
use Magento\Framework\Event\Observer;
use Magento\Framework\Message\ManagerInterface;
use Magento\Quote\Api\CartRepositoryInterface;
use Magento\Quote\Api\Data\CartInterface;
use Magento\Quote\Api\Data\CartItemInterface;
use Mollie\Subscriptions\Service\Cart\CustomerAlreadyHasSubscriptionToProduct;

class PreventDuplicateSubscriptionProductsInCart
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

    public function afterLoadCustomerQuote(Session $subject, Session $result): Session
    {
        $cart = $subject->getQuote();
        $items = $cart->getItems();
        if (!$items) {
            return $result;
        }

        $this->itemRemoved = false;
        foreach ($items as $item) {
            if ($this->customerAlreadyHasSubscriptionToProduct->execute($cart->getCustomer(), $item->getProduct())) {
                $this->removeItem($item, $cart);
                $this->itemRemoved = true;
            }
        }

        if ($this->itemRemoved) {
            $this->cartRepository->save($cart);
        }

        return $result;
    }

    /**
     * @param CartItemInterface $item
     * @param CartInterface $cart
     * @return void
     */
    public function removeItem(CartItemInterface $item, CartInterface $cart): void
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
