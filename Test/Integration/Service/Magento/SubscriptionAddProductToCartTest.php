<?php
/*
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Mollie\Subscriptions\Test\Integration\Service\Magento;

use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\Framework\App\ProductMetadataInterface;
use Magento\Quote\Api\CartManagementInterface;
use Magento\Quote\Api\CartRepositoryInterface;
use Magento\Quote\Api\Data\AddressInterfaceFactory;
use Magento\Quote\Model\Quote;
use Mollie\Api\Resources\Subscription;
use Mollie\Payment\Test\Integration\IntegrationTestCase;
use Mollie\Subscriptions\Service\Magento\SubscriptionAddProductToCart;
use stdClass;

class SubscriptionAddProductToCartTest extends IntegrationTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $version = $this->objectManager->get(ProductMetadataInterface::class)->getVersion();
        if (version_compare($version, '2.4.0', '<')) {
            $this->markTestSkipped('TaxConfig caching behaviour on Magento < 2.4.0 interferes with these tests');
        }
    }

    /**
     * @see https://github.com/mollie/magento2-subscriptions/issues/104
     *
     * @magentoDataFixture Magento/Customer/_files/customer_with_addresses.php
     * @magentoDataFixture Magento/Catalog/_files/product_simple.php
     * @magentoDataFixture Magento/Tax/_files/tax_rule_region_1_al.php
     * @magentoConfigFixture default_store tax/calculation/price_includes_tax 1
     */
    public function testSetsGrossPriceAsCustomPriceInTaxInclusiveStore(): void
    {
        $cart = $this->buildCartWithAlabamaBillingAddress();

        $subscription = $this->objectManager->get(Subscription::class);
        $subscription->amount = new stdClass();
        $subscription->amount->value = 10.75;
        $subscription->amount->currency = 'EUR';
        $subscription->metadata = new stdClass();
        $subscription->metadata->quantity = '1';
        $subscription->metadata->sku = 'simple';

        $instance = $this->objectManager->create(SubscriptionAddProductToCart::class);
        $instance->execute($cart, $subscription);

        $items = array_values($cart->getAllItems());
        $item = $items[0];

        // In a tax-inclusive store the subscription amount (10.75) is already the gross price.
        // Setting original_custom_price to the net (10.75 / 1.075 = 10.00) causes Magento to
        // treat it as inclusive and strip tax again, recording 10.00 instead of 10.75.
        $this->assertEqualsWithDelta(
            $subscription->amount->value,
            (float)$item->getOriginalCustomPrice(),
            0.01,
            'In a tax-inclusive store, original_custom_price must be the gross subscription amount'
        );
    }

    /**
     * @magentoDataFixture Magento/Customer/_files/customer_with_addresses.php
     * @magentoDataFixture Magento/Catalog/_files/product_simple.php
     * @magentoDataFixture Magento/Tax/_files/tax_rule_region_1_al.php
     * @magentoConfigFixture default_store tax/calculation/price_includes_tax 0
     */
    public function testSetsNetPriceAsCustomPriceInTaxExclusiveStore(): void
    {
        $cart = $this->buildCartWithAlabamaBillingAddress();

        $subscription = $this->objectManager->get(Subscription::class);
        $subscription->amount = new stdClass();
        $subscription->amount->value = 10.75;
        $subscription->amount->currency = 'EUR';
        $subscription->metadata = new stdClass();
        $subscription->metadata->quantity = '1';
        $subscription->metadata->sku = 'simple';

        $instance = $this->objectManager->create(SubscriptionAddProductToCart::class);
        $instance->execute($cart, $subscription);

        $items = array_values($cart->getAllItems());
        $item = $items[0];

        // In a tax-exclusive store the gross Mollie amount must be divided to get the net
        // price, which Magento then uses as the base for adding tax.
        $expectedNet = $subscription->amount->value / 1.075;

        $this->assertEqualsWithDelta(
            $expectedNet,
            (float)$item->getOriginalCustomPrice(),
            0.01,
            'In a tax-exclusive store, original_custom_price must be the net price (gross / (1 + rate))'
        );
    }

    private function buildCartWithAlabamaBillingAddress(): Quote
    {
        $customerRepository = $this->objectManager->get(CustomerRepositoryInterface::class);
        $customer = $customerRepository->get('customer_with_addresses@test.com');

        $cartManagement = $this->objectManager->get(CartManagementInterface::class);
        $cartRepository = $this->objectManager->get(CartRepositoryInterface::class);

        $cartId = $cartManagement->createEmptyCartForCustomer($customer->getId());
        /** @var Quote $cart */
        $cart = $cartRepository->get($cartId);

        $address = $customer->getAddresses()[0];

        $addressFactory = $this->objectManager->get(AddressInterfaceFactory::class);
        $quoteAddress = $addressFactory->create();
        $quoteAddress->setFirstname($address->getFirstname());
        $quoteAddress->setLastname($address->getLastname());
        $quoteAddress->setStreet($address->getStreet());
        $quoteAddress->setCity($address->getCity());
        $quoteAddress->setRegionId($address->getRegionId());
        $quoteAddress->setCountryId($address->getCountryId());
        $quoteAddress->setPostcode($address->getPostcode());
        $quoteAddress->setTelephone($address->getTelephone());

        $cart->setBillingAddress($quoteAddress);
        $cart->setShippingAddress($quoteAddress);

        return $cart;
    }
}
