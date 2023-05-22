<?php

namespace Mollie\Subscriptions\Test\Integration\Cron;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Mollie\Payment\Test\Integration\IntegrationTestCase;
use Mollie\Subscriptions\Api\Data\SubscriptionToProductInterface;
use Mollie\Subscriptions\Api\SubscriptionToProductRepositoryInterface;
use Mollie\Subscriptions\Cron\SendPrePaymentReminderEmailCron;
use Mollie\Subscriptions\Model\Adminhtml\Backend\SaveCronValue;
use Mollie\Subscriptions\Service\Email\SendPrepaymentReminderEmail;
use Mollie\Subscriptions\Service\Mollie\CheckIfSubscriptionIsActive;

class SendPrePaymentReminderEmailCronTest extends IntegrationTestCase
{
    public function testHasADefaultScheduleAvailable(): void
    {
        /** @var ScopeConfigInterface $config */
        $config = $this->objectManager->get(ScopeConfigInterface::class);

        $value = $config->getValue(SaveCronValue::CRON_SCHEDULE_PATH);

        $this->assertEquals('0 1 * * *', $value);
    }

    /**
     * @magentoDbIsolation enabled
     * @magentoConfigFixture default_store mollie_subscriptions/prepayment_reminder/enabled 1
     */
    public function testSendsPrePaymentEmailWhenLastReminderDateIsNull(): void
    {
        $spy = $this->any();
        $sendPrepaymentReminderEmailMock = $this->createMock(SendPrepaymentReminderEmail::class);
        $sendPrepaymentReminderEmailMock->expects($spy)->method('execute');

        $checkIfSubscriptionIsActiveMock = $this->createMock(CheckIfSubscriptionIsActive::class);
        $checkIfSubscriptionIsActiveMock->method('execute')->willReturn(true);

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
            'checkIfSubscriptionIsActive' => $checkIfSubscriptionIsActiveMock,
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

        $checkIfSubscriptionIsActiveMock = $this->createMock(CheckIfSubscriptionIsActive::class);
        $checkIfSubscriptionIsActiveMock->method('execute')->willReturn(true);

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
            'checkIfSubscriptionIsActive' => $checkIfSubscriptionIsActiveMock,
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

        $checkIfSubscriptionIsActiveMock = $this->createMock(CheckIfSubscriptionIsActive::class);
        $checkIfSubscriptionIsActiveMock->method('execute')->willReturn(true);

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
            'checkIfSubscriptionIsActive' => $checkIfSubscriptionIsActiveMock,
        ]);

        $instance->execute();

        $this->assertEquals(0, $spy->getInvocationCount());
    }

    /**
     * @magentoDbIsolation enabled
     * @magentoConfigFixture default_store mollie_subscriptions/prepayment_reminder/enabled 1
     */
    public function testIfTheSubscriptionIsNotActiveTheEmailWillNotBeSent(): void
    {
        $spy = $this->any();
        $sendPrepaymentReminderEmailMock = $this->createMock(SendPrepaymentReminderEmail::class);
        $sendPrepaymentReminderEmailMock->expects($spy)->method('execute');

        $checkIfSubscriptionIsActiveMock = $this->createMock(CheckIfSubscriptionIsActive::class);
        $checkIfSubscriptionIsActiveMock->method('execute')->willReturn(false);

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
            'checkIfSubscriptionIsActive' => $checkIfSubscriptionIsActiveMock,
        ]);

        $instance->execute();

        $this->assertEquals(0, $spy->getInvocationCount());
    }
}
