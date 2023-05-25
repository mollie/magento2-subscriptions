<?php

namespace Mollie\Subscriptions\Test\Integration\Cron;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Mollie\Payment\Test\Integration\IntegrationTestCase;
use Mollie\Subscriptions\Api\Data\SubscriptionToProductInterface;
use Mollie\Subscriptions\Api\SubscriptionToProductRepositoryInterface;
use Mollie\Subscriptions\Cron\SendPrePaymentReminderEmailCron;
use Mollie\Subscriptions\Model\Adminhtml\Backend\SaveCronValue;
use Mollie\Subscriptions\Model\SubscriptionToProduct;
use Mollie\Subscriptions\Service\Email\SendPrepaymentReminderEmail;
use Mollie\Subscriptions\Service\Mollie\CheckIfSubscriptionIsActive;

class SendPrePaymentReminderEmailCronTest extends IntegrationTestCase
{
    /**
     * @var SubscriptionToProductRepositoryInterface
     */
    private $repository;

    public function setUpWithoutVoid()
    {
        $this->repository = $this->objectManager->create(SubscriptionToProductRepositoryInterface::class);
    }

    public function tearDown(): void
    {
        /** @var SubscriptionToProduct $model */
        $model = $this->objectManager->create(SubscriptionToProduct::class);
        $model->getCollection()->getConnection()->truncateTable($model->getResource()->getMainTable());
    }

    public function testHasADefaultScheduleAvailable(): void
    {
        /** @var ScopeConfigInterface $config */
        $config = $this->objectManager->get(ScopeConfigInterface::class);

        $value = $config->getValue(SaveCronValue::CRON_SCHEDULE_PATH);

        $this->assertEquals('0 1 * * *', $value);
    }

    /**
     * @magentoConfigFixture default_store mollie_subscriptions/prepayment_reminder/enabled 0
     */
    public function testDoesNothingWhenNotEnabled(): void
    {
        $this->createDefaultModels();

        $spy = $this->any();
        $sendPrepaymentReminderEmailMock = $this->createMock(SendPrepaymentReminderEmail::class);
        $sendPrepaymentReminderEmailMock->expects($spy)->method('execute');

        $checkIfSubscriptionIsActiveMock = $this->createMock(CheckIfSubscriptionIsActive::class);
        $checkIfSubscriptionIsActiveMock->method('execute')->willReturn(true);

        /** @var SendPrePaymentReminderEmailCron $instance */
        $instance = $this->objectManager->create(SendPrePaymentReminderEmailCron::class, [
            'sendPrepaymentReminderEmail' => $sendPrepaymentReminderEmailMock,
            'checkIfSubscriptionIsActive' => $checkIfSubscriptionIsActiveMock,
        ]);

        $instance->execute();

        $this->assertEquals(0, $spy->getInvocationCount());
    }

    /**
     * @magentoConfigFixture default_store mollie_subscriptions/prepayment_reminder/enabled 1
     */
    public function testDoesNothingWhenTheSubscriptionIsNotActive(): void
    {
        $this->createDefaultModels();

        $spy = $this->any();
        $sendPrepaymentReminderEmailMock = $this->createMock(SendPrepaymentReminderEmail::class);
        $sendPrepaymentReminderEmailMock->expects($spy)->method('execute');

        $checkIfSubscriptionIsActiveMock = $this->createMock(CheckIfSubscriptionIsActive::class);
        $checkIfSubscriptionIsActiveMock->method('execute')->willReturn(false);

        /** @var SendPrePaymentReminderEmailCron $instance */
        $instance = $this->objectManager->create(SendPrePaymentReminderEmailCron::class, [
            'sendPrepaymentReminderEmail' => $sendPrepaymentReminderEmailMock,
            'checkIfSubscriptionIsActive' => $checkIfSubscriptionIsActiveMock,
        ]);

        $instance->execute();

        $this->assertEquals(0, $spy->getInvocationCount());
    }

    /**
     * @magentoConfigFixture default_store mollie_subscriptions/prepayment_reminder/enabled 1
     */
    public function testSendsTheEmailWhenRequired(): void
    {
        $models = $this->createDefaultModels();

        $spy = $this->any();
        $sendPrepaymentReminderEmailMock = $this->createMock(SendPrepaymentReminderEmail::class);
        $sendPrepaymentReminderEmailMock->expects($spy)->method('execute');

        $checkIfSubscriptionIsActiveMock = $this->createMock(CheckIfSubscriptionIsActive::class);
        $checkIfSubscriptionIsActiveMock->method('execute')->willReturn(true);

        /** @var SendPrePaymentReminderEmailCron $instance */
        $instance = $this->objectManager->create(SendPrePaymentReminderEmailCron::class, [
            'sendPrepaymentReminderEmail' => $sendPrepaymentReminderEmailMock,
            'checkIfSubscriptionIsActive' => $checkIfSubscriptionIsActiveMock,
        ]);

        $instance->execute();

        $this->assertEquals(2, $spy->getInvocationCount());

        // Assert that the last_reminder_date is updated.
        $reloaded = $this->repository->get($models['future']->getEntityId());
        $this->assertEquals((new \DateTimeImmutable())->format('Y-m-d'), $reloaded->getLastReminderDate());

        $reloaded = $this->repository->get($models['future_with_last_reminder_date']->getEntityId());
        $this->assertEquals((new \DateTimeImmutable())->format('Y-m-d'), $reloaded->getLastReminderDate());
    }

    /**
     * @return SubscriptionToProductInterface[]
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    private function createDefaultModels(): array
    {
        $today = new \DateTimeImmutable();
        $future = new \DateTimeImmutable('+3 day');

        return [
            'today' => $this->repository->save($this->createModel($today)),
            'today_with_last_reminder_date' => $this->repository->save($this->createModelWithLastReminderDate($today)),
            'future' => $this->repository->save($this->createModel($future)),
            'future_with_last_reminder_date' => $this->repository->save($this->createModelWithLastReminderDate($future)),
        ];
    }

    private function createModel(\DateTimeImmutable $date, \DateTimeImmutable $lastReminderDate = null): SubscriptionToProductInterface
    {
        /** @var SubscriptionToProductInterface $model */
        $model = $this->objectManager->create(SubscriptionToProductInterface::class);
        $model->setNextPaymentDate($date->format('Y-m-d'));

        $model->setCustomerId(1);
        $model->setSubscriptionId(1);
        $model->setProductId(1);

        if ($lastReminderDate) {
            $model->setLastReminderDate($lastReminderDate->format('Y-m-d'));
        }

        return $model;
    }

    private function createModelWithLastReminderDate(\DateTimeImmutable $date): SubscriptionToProductInterface
    {
        return $this->createModel(
            $date,
            $date->sub(new \DateInterval('P7D'))
        );
    }
}
