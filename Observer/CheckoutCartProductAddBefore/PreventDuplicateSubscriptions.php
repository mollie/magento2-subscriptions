<?php

namespace Mollie\Subscriptions\Observer\CheckoutCartProductAddBefore;

use Magento\Customer\Model\Session;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\Exception\LocalizedException;
use Mollie\Subscriptions\Service\Cart\CustomerAlreadyHasSubscriptionToProduct;

class PreventDuplicateSubscriptions implements ObserverInterface
{
    /**
     * @var Session
     */
    private $session;

    /**
     * @var CustomerAlreadyHasSubscriptionToProduct
     */
    private $customerAlreadyHasSubscriptionToProduct;

    public function __construct(
        Session $session,
        CustomerAlreadyHasSubscriptionToProduct $customerAlreadyHasSubscriptionToProduct
    ) {
        $this->session = $session;
        $this->customerAlreadyHasSubscriptionToProduct = $customerAlreadyHasSubscriptionToProduct;
    }

    public function execute(Observer $observer)
    {
        $product = $observer->getData('product');
        if (!$product->getData('mollie_subscription_product')) {
            throw new \Exception('No mollie subscription product');
            return;
        }

        if (!$this->session->isLoggedIn()) {
            throw new \Exception('Not logged in');
            return;
        }

        if ($this->customerAlreadyHasSubscriptionToProduct->execute($this->session->getCustomer(), $product)) {
            throw new LocalizedException(__('You already have a subscription to this product.'));
        }
    }
}
