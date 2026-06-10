<?php

namespace Mollie\Subscriptions\Test\Integration\Service\Email;

use Mollie\Api\Endpoints\CustomerEndpoint;
use Mollie\Api\Fake\MockResponse;
use Mollie\Api\Fake\SequenceMockResponse;
use Mollie\Api\Http\Requests\GetCustomerRequest;
use Mollie\Api\MollieApiClient;
use Mollie\Payment\Test\Integration\IntegrationTestCase;
use Mollie\Subscriptions\Api\Data\SubscriptionToProductInterface;
use Mollie\Subscriptions\Service\Email\SubscriptionToProductEmailVariables;
use Mollie\Subscriptions\Service\Mollie\MollieSubscriptionApi;
use Mollie\Subscriptions\Test\Fakes\Service\Mollie\MollieSubscriptionApiFake;

class SubscriptionToProductEmailVariablesTest extends IntegrationTestCase
{
    public function testDoesNotCacheTheCustomer(): void
    {
        $client = MollieApiClient::fake([
            GetCustomerRequest::class => new SequenceMockResponse(
                MockResponse::ok('{"id": 1}'),
                MockResponse::ok('{"id": 2}'),
            ),
        ]);

        /** @var MollieSubscriptionApiFake $fakeMollieApiClient */
        $fakeMollieApiClient = $this->objectManager->get(MollieSubscriptionApiFake::class);
        $fakeMollieApiClient->setInstance($client);
        $this->objectManager->addSharedInstance($fakeMollieApiClient, MollieSubscriptionApi::class);

        /** @var SubscriptionToProductEmailVariables $instance */
        $instance = $this->objectManager->create(SubscriptionToProductEmailVariables::class);

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
