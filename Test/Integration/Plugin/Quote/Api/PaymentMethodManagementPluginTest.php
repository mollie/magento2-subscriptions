<?php
/*
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Mollie\Subscriptions\Test\Integration\Plugin\Quote\Api;

use Magento\Checkout\Model\Session;
use Magento\OfflinePayments\Model\Checkmo;
use Magento\Quote\Api\PaymentMethodManagementInterface;
use Magento\TestFramework\ObjectManager;
use Mollie\Payment\Model\Methods\Ideal;
use Mollie\Payment\Model\Methods\Voucher;
use Mollie\Payment\Test\Integration\IntegrationTestCase;
use Mollie\Subscriptions\Plugin\Quote\Api\PaymentMethodManagementPlugin;
use Mollie\Subscriptions\Service\Cart\CartContainsSubscriptionProduct;

class PaymentMethodManagementPluginTest extends IntegrationTestCase
{
    /**
     * @var Session
     */
    private $session;

    protected function setUpWithoutVoid()
    {
        $this->session = $this->objectManager->create(Session::class);
    }

    /**
     * @magentoAppIsolation enabled
     * @magentoDataFixture Magento/Checkout/_files/quote_with_simple_product.php
     * @throws \ReflectionException
     */
    public function testDoesNothingWhenNoSubscriptionsInCart()
    {
        $cartContainsSubscriptionProductMock = $this->createMock(CartContainsSubscriptionProduct::class);
        $cartContainsSubscriptionProductMock->method('check')->willReturn(false);

        /** @var PaymentMethodManagementPlugin $instance */
        $instance = $this->objectManager->create(PaymentMethodManagementPlugin::class, [
            'cartContainsSubscriptionProduct' => $cartContainsSubscriptionProductMock,
        ]);

        $subject = $this->objectManager->create(PaymentMethodManagementInterface::class);

        $result = $instance->afterGetList($subject, $this->getPaymentMethodList(), $this->session->getQuoteId());

        $this->assertCount(3, $result);
    }

    /**
     * @magentoAppIsolation enabled
     * @magentoDataFixture Magento/Checkout/_files/quote_with_simple_product.php
     * @throws \ReflectionException
     */
    public function testFiltersNonAllowedMethodsWhenCartContainsSubscriptionProduct()
    {
        $cartContainsSubscriptionProductMock = $this->createMock(CartContainsSubscriptionProduct::class);
        $cartContainsSubscriptionProductMock->method('check')->willReturn(true);

        /** @var PaymentMethodManagementPlugin $instance */
        $instance = $this->objectManager->create(PaymentMethodManagementPlugin::class, [
            'cartContainsSubscriptionProduct' => $cartContainsSubscriptionProductMock,
        ]);

        $subject = $this->objectManager->create(PaymentMethodManagementInterface::class);

        $result = $instance->afterGetList($subject, $this->getPaymentMethodList(), $this->session->getQuoteId());

        $this->assertCount(1, $result);
        $this->assertInstanceOf(Ideal::class, array_shift($result));
    }

    private function getPaymentMethodList()
    {
        return [
            $this->objectManager->create(Ideal::class),
            $this->objectManager->create(Voucher::class),
            $this->objectManager->create(Checkmo::class),
        ];
    }
}
