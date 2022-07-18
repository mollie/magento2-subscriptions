<?php

namespace Mollie\Subscriptions\Test\Integration\Controller\Api;

use Magento\Framework\Encryption\Encryptor;
use Magento\TestFramework\Request;
use Magento\TestFramework\TestCase\AbstractController as ControllerTestCase;
use Mollie\Api\Endpoints\PaymentEndpoint;
use Mollie\Api\Exceptions\ApiException;
use Mollie\Payment\Model\Mollie;
use Mollie\Payment\Test\Fakes\FakeEncryptor;

class WebhookTest extends ControllerTestCase
{
    public function testAcceptsPost()
    {
        $instance = $this->_objectManager->get(FakeEncryptor::class);
        $instance->addReturnValue('', 'test_dummyapikeythatisvalidandislongenough');

        $this->_objectManager->addSharedInstance($instance, Encryptor::class);

        $paymentEndpointMock = $this->createMock(PaymentEndpoint::class);
        $paymentEndpointMock->method('get')->willThrowException(new ApiException('Invalid transaction (Test)'));

        /** @var Mollie $mollie */
        $mollie = $this->_objectManager->get(Mollie::class);
        $api = $mollie->getMollieApi();
        $api->payments = $paymentEndpointMock;

        $mollieMock = $this->createMock(Mollie::class);
        $mollieMock->method('getMollieApi')->willReturn($api);
        $this->_objectManager->addSharedInstance($mollieMock, Mollie::class);

        $this->getRequest()->setMethod(Request::METHOD_POST);
        $this->getRequest()->setParams([
            'id' => 'ord_123ABC',
        ]);

        $this->dispatch('mollie-subscriptions/api/webhook');

        $this->assert404NotFound();
    }
}
