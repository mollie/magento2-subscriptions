<?php

namespace Mollie\Subscriptions\Test\Integration\Service\Email;

use Mollie\Api\Endpoints\CustomerEndpoint;
use Mollie\Api\MollieApiClient;
use Mollie\Api\Resources\Customer;
use Mollie\Payment\Model\Mollie;
use Mollie\Payment\Test\Integration\IntegrationTestCase;
use Mollie\Subscriptions\Api\Data\SubscriptionToProductInterface;
use Mollie\Subscriptions\Service\Email\SubscriptionToProductEmailVariables;
use Mollie\Subscriptions\Service\Mollie\MollieSubscriptionApi;

class SubscriptionToProductEmailVariablesTest extends IntegrationTestCase
{
    public function testDoesNotCacheTheCustomer(): void
    {
        $client = new MollieApiClient();

        $client->customers = new class($client) extends CustomerEndpoint {
            private $customers;

            public function __construct(MollieApiClient $api)
            {
                $customer1 = new Customer($api);
                $customer1->id = 1;

                $customer2 = new Customer($api);
                $customer2->id = 2;

                $this->customers = [
                    1 => $customer1,
                    2 => $customer2
                ];
            }

            public function get($id, array $parameters = [])
            {
                return $this->customers[$id];
            }
        };

        $mollieSubscriptionApiMock = $this->createMock(MollieSubscriptionApi::class);
        $mollieSubscriptionApiMock->method('loadByStore')->willReturn($client);

        /** @var SubscriptionToProductEmailVariables $instance */
        $instance = $this->objectManager->create(SubscriptionToProductEmailVariables::class, [
            'mollieSubscriptionApi' => $mollieSubscriptionApiMock,
        ]);

        $model1 = $this->objectManager->create(SubscriptionToProductInterface::class);
        $model1->setCustomerId(1);

        $model2 = $this->objectManager->create(SubscriptionToProductInterface::class);
        $model2->setCustomerId(2);

        $this->assertEquals(1, $instance->getMollieCustomer($model1)->id);
        $this->assertEquals(2, $instance->getMollieCustomer($model2)->id);

        $this->assertNotEquals(
            $instance->getMollieCustomer($model1)->id,
            $instance->getMollieCustomer($model2)->id
        );
    }
}
