<?php

namespace Mollie\Subscriptions\Test\Integration\Service\Email;

use Mollie\Payment\Test\Integration\IntegrationTestCase;
use Mollie\Subscriptions\Api\Data\SubscriptionToProductInterface;
use Mollie\Subscriptions\Api\SubscriptionToProductRepositoryInterface;
use Mollie\Subscriptions\Model\SubscriptionToProduct;
use Mollie\Subscriptions\Service\Email\RetrieveRecordsForPrePaymentReminder;

class RetrieveRecordsForPrePaymentReminderTest extends IntegrationTestCase
{
    /**
     * @var SubscriptionToProductRepositoryInterface
     */
    private $repository;

    protected function setUpWithoutVoid()
    {
        $this->repository = $this->objectManager->create(SubscriptionToProductRepositoryInterface::class);
    }

    public function createDefaultModels(): void
    {
        $today = new \DateTimeImmutable();
        $future = new \DateTimeImmutable('+3 day');

        $this->repository->save($this->createModel($today));
        $this->repository->save($this->createModelWithLastReminderDate($today));
        $this->repository->save($this->createModel($future));
        $this->repository->save($this->createModelWithLastReminderDate($future));
    }

    public function tearDown(): void
    {
        /** @var SubscriptionToProduct $model */
        $model = $this->objectManager->create(SubscriptionToProduct::class);
        $model->getCollection()->getConnection()->truncateTable($model->getResource()->getMainTable());
    }

    /**
     * @see \Mollie\Subscriptions\Service\Email\RetrieveRecordsForPrePaymentReminder::execute()
     * @return void
     */
    public function testRetrievesRecords(): void
    {
        $this->createDefaultModels();

        $today = new \DateTimeImmutable();
        $future = new \DateTimeImmutable('+3 day');

        /** @var RetrieveRecordsForPrePaymentReminder $instance */
        $instance = $this->objectManager->create(RetrieveRecordsForPrePaymentReminder::class);
        $subscriptions = $instance->execute($today);

        $this->assertEquals($future->format('Y-m-d'), $subscriptions->getItems()[0]->getNextPaymentDate());
        $this->assertEquals(2, $subscriptions->getTotalCount());
    }

    /**
     * @see \Mollie\Subscriptions\Service\Email\RetrieveRecordsForPrePaymentReminder::execute()
     * @return void
     */
    public function testRetrievesNothingWhenThereShouldBeNothing(): void
    {
        $this->createDefaultModels();

        $today = new \DateTimeImmutable('+1 day');

        /** @var RetrieveRecordsForPrePaymentReminder $instance */
        $instance = $this->objectManager->create(RetrieveRecordsForPrePaymentReminder::class);
        $subscriptions = $instance->execute($today);

        $this->assertEquals(0, $subscriptions->getTotalCount());
    }

    public function testReceivesNothingWhenLastPaymentDateIsToday(): void
    {
        $today = new \DateTimeImmutable();
        $future = new \DateTimeImmutable('+3 day');

        $this->repository->save($this->createModel($future, $today));

        /** @var RetrieveRecordsForPrePaymentReminder $instance */
        $instance = $this->objectManager->create(RetrieveRecordsForPrePaymentReminder::class);
        $subscriptions = $instance->execute($today);

        $this->assertEquals(0, $subscriptions->getTotalCount());
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
