<?php

namespace Mollie\Subscriptions\Test\Integration\Cron;

use Mollie\Payment\Test\Integration\IntegrationTestCase;
use Mollie\Subscriptions\Api\Data\SubscriptionToProductInterface;
use Mollie\Subscriptions\Api\SubscriptionToProductRepositoryInterface;
use Mollie\Subscriptions\Cron\SendPrePaymentReminderEmailCron;
use Mollie\Subscriptions\Service\Email\SendPrepaymentReminderEmail;

class SendPrePaymentReminderEmailCronTest extends IntegrationTestCase
{
    /**
     * @magentoDbIsolation enabled
     * @magentoConfigFixture default_store mollie_subscriptions/prepayment_reminder/enabled 1
     */
    public function testSendsPrePaymentEmailWhenLastReminderDateIsNull(): void
    {
        $spy = $this->any();
        $sendPrepaymentReminderEmailMock = $this->createMock(SendPrepaymentReminderEmail::class);
        $sendPrepaymentReminderEmailMock->expects($spy)->method('execute');

        /** @var SubscriptionToProductInterface $model */
        $model = $this->objectManager->create(SubscriptionToProductInterface::class);
        $model->setCustomerId('cst_fakenumber');
        $model->setSubscriptionId('sub_fakesubscription');
        $model->setProductId(1);
        $model->setStoreId(1);

        $model->setLastReminderDate(null);

        $this->objectManager->get(SubscriptionToProductRepositoryInterface::class)->save($model);

        /** @var SendPrePaymentReminderEmailCron $instance */
        $instance = $this->objectManager->create(SendPrePaymentReminderEmailCron::class, [
            'sendPrepaymentReminderEmail' => $sendPrepaymentReminderEmailMock,
        ]);

        $instance->execute();

        $this->assertEquals(1, $spy->getInvocationCount());
    }

    /**
     * @magentoDbIsolation enabled
     * @magentoConfigFixture default_store mollie_subscriptions/prepayment_reminder/enabled 1
     */
    public function testSendsPrePaymentEmailWhenLastReminderDateIsInThePast(): void
    {
        $yesterday = (new \DateTime())->modify('-1 day')->format('Y-m-d');

        $spy = $this->any();
        $sendPrepaymentReminderEmailMock = $this->createMock(SendPrepaymentReminderEmail::class);
        $sendPrepaymentReminderEmailMock->expects($spy)->method('execute');

        /** @var SubscriptionToProductInterface $model */
        $model = $this->objectManager->create(SubscriptionToProductInterface::class);
        $model->setCustomerId('cst_fakenumber');
        $model->setSubscriptionId('sub_fakesubscription');
        $model->setProductId(1);
        $model->setStoreId(1);

        $model->setLastReminderDate($yesterday);

        $this->objectManager->get(SubscriptionToProductRepositoryInterface::class)->save($model);

        /** @var SendPrePaymentReminderEmailCron $instance */
        $instance = $this->objectManager->create(SendPrePaymentReminderEmailCron::class, [
            'sendPrepaymentReminderEmail' => $sendPrepaymentReminderEmailMock,
        ]);

        $instance->execute();

        $this->assertEquals(1, $spy->getInvocationCount());
    }

    /**
     * @magentoDbIsolation enabled
     * @magentoConfigFixture default_store mollie_subscriptions/prepayment_reminder/enabled 1
     */
    public function testDoesNotSendsPrePaymentEmailWhenLastReminderDateIsToday(): void
    {
        $today = (new \DateTime())->format('Y-m-d');

        $spy = $this->any();
        $sendPrepaymentReminderEmailMock = $this->createMock(SendPrepaymentReminderEmail::class);
        $sendPrepaymentReminderEmailMock->expects($spy)->method('execute');

        /** @var SubscriptionToProductInterface $model */
        $model = $this->objectManager->create(SubscriptionToProductInterface::class);
        $model->setCustomerId('cst_fakenumber');
        $model->setSubscriptionId('sub_fakesubscription');
        $model->setProductId(1);
        $model->setStoreId(1);

        $model->setLastReminderDate($today);

        $this->objectManager->get(SubscriptionToProductRepositoryInterface::class)->save($model);

        /** @var SendPrePaymentReminderEmailCron $instance */
        $instance = $this->objectManager->create(SendPrePaymentReminderEmailCron::class, [
            'sendPrepaymentReminderEmail' => $sendPrepaymentReminderEmailMock,
        ]);

        $instance->execute();

        $this->assertEquals(0, $spy->getInvocationCount());
    }
}