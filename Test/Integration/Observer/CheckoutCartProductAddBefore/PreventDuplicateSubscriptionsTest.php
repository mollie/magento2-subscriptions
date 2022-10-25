<?php

namespace Mollie\Subscriptions\Test\Integration\CheckoutCartProductAddBefore;

use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Customer\Model\Customer;
use Magento\Framework\Event\Observer;
use Magento\Framework\Exception\LocalizedException;
use Mollie\Payment\Test\Integration\IntegrationTestCase;
use Mollie\Subscriptions\Observer\CheckoutCartProductAddBefore\PreventDuplicateSubscriptions;
use Mollie\Subscriptions\Service\Cart\CustomerAlreadyHasSubscriptionToProduct;
use Mollie\Subscriptions\Test\Fakes\Service\Cart\CustomerAlreadyHasSubscriptionToProductFake;

class PreventDuplicateSubscriptionsTest extends IntegrationTestCase
{
    public function testDoesNothingWhenNotASubscriptionProduct(): void
    {
        $fake = $this->objectManager->create(CustomerAlreadyHasSubscriptionToProductFake::class);
        $fake->shouldNotBeCalled();
        $this->objectManager->addSharedInstance($fake, CustomerAlreadyHasSubscriptionToProduct::class);

        $observer = new Observer([
            'product' => $this->objectManager->create(ProductInterface::class)
        ]);

        /** @var PreventDuplicateSubscriptions $instance */
        $instance = $this->objectManager->create(PreventDuplicateSubscriptions::class);
        $instance->execute($observer);
    }

    public function testDoesNothingWhenNotLoggedIn(): void
    {
        $fake = $this->objectManager->create(CustomerAlreadyHasSubscriptionToProductFake::class);
        $fake->shouldNotBeCalled();
        $this->objectManager->addSharedInstance($fake, CustomerAlreadyHasSubscriptionToProduct::class);

        $customerSessionMock = $this->createMock(\Magento\Customer\Model\Session::class);
        $customerSessionMock->method('isLoggedIn')->willReturn(false);

        $product = $this->objectManager->create(ProductInterface::class);
        $product->setData('mollie_subscription_product', ['is_recurring' => true]);

        $observer = new Observer([
            'product' => $product
        ]);

        /** @var PreventDuplicateSubscriptions $instance */
        $instance = $this->objectManager->create(PreventDuplicateSubscriptions::class, [
            'session' => $customerSessionMock,
        ]);
        $instance->execute($observer);
    }

    /**
     * @throws LocalizedException
     * @return void
     */
    public function testThrowsExceptionWhenSubscriptionAlreadyPresent(): void
    {
        $this->expectException(LocalizedException::class);
        $this->expectExceptionMessage('You already have a subscription to this product.');

        $fake = $this->objectManager->create(CustomerAlreadyHasSubscriptionToProductFake::class);
        $fake->alwaysReturnsTrue();
        $this->objectManager->addSharedInstance($fake, CustomerAlreadyHasSubscriptionToProduct::class);

        $customerSessionMock = $this->createMock(\Magento\Customer\Model\Session::class);
        $customerSessionMock->method('isLoggedIn')->willReturn(true);
        $customerSessionMock->method('getCustomer')->willReturn(
            $this->objectManager->create(Customer::class)
        );

        $product = $this->objectManager->create(ProductInterface::class);
        $product->setData('mollie_subscription_product', ['is_recurring' => true]);

        $observer = new Observer([
            'product' => $product
        ]);

        /** @var PreventDuplicateSubscriptions $instance */
        $instance = $this->objectManager->create(PreventDuplicateSubscriptions::class, [
            'session' => $customerSessionMock,
        ]);
        $instance->execute($observer);
    }
}
