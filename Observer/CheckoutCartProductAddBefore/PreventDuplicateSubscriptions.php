<?php

declare(strict_types=1);

namespace Mollie\Subscriptions\Observer\CheckoutCartProductAddBefore;

use Magento\Customer\Model\Session;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\Exception\LocalizedException;
use Mollie\Subscriptions\Service\Cart\CustomerAlreadyHasSubscriptionToProduct;

class PreventDuplicateSubscriptions implements ObserverInterface
{
    public function __construct(
        private readonly Session $session,
        private readonly CustomerAlreadyHasSubscriptionToProduct $customerAlreadyHasSubscriptionToProduct
    ) {
    }

    public function execute(Observer $observer): void
    {
        $product = $observer->getData('product');
        if (!$product->getData('mollie_subscription_product')) {
            return;
        }

        if (!$this->session->isLoggedIn()) {
            return;
        }

        $customer = $this->session->getCustomer();
        if ($this->customerAlreadyHasSubscriptionToProduct->execute($customer->getDataModel(), $product)) {
            throw new LocalizedException(__('You already have a subscription to this product.'));
        }
    }
}
