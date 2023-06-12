<?php

declare(strict_types=1);

namespace Mollie\Subscriptions\Test\Integration;

use Mollie\Payment\Test\Integration\IntegrationTestCase;
use Mollie\Subscriptions\Config;

class ConfigTest extends IntegrationTestCase
{
    /**
     * @magentoConfigFixture default_store mollie_subscriptions/emails/disable_new_order_confirmation 1
     * @return void
     */
    public function testDisableNewOrderConfirmationWhenEnabled(): void
    {
        /** @var Config $instance */
        $instance = $this->objectManager->create(Config::class);

        $this->assertTrue($instance->disableNewOrderConfirmation());
    }

    /**
     * @magentoConfigFixture default_store mollie_subscriptions/emails/disable_new_order_confirmation 0
     * @return void
     */
    public function testDisableNewOrderConfirmationWhenDisabled(): void
    {
        /** @var Config $instance */
        $instance = $this->objectManager->create(Config::class);

        $this->assertFalse($instance->disableNewOrderConfirmation());
    }
}
