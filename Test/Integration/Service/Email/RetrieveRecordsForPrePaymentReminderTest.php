<?php
/*
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Mollie\Subscriptions\Test\Integration\Service\Email;

use DateInterval;
use DateTimeImmutable;
use Magento\Framework\DB\Adapter\ConnectionException;
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

    public function createDefaultModels(): void
    {
        $today = new DateTimeImmutable();
        $future = new DateTimeImmutable('+3 day');

        $this->repository->save($this->createModel($today));
        $this->repository->save($this->createModelWithLastReminderDate($today));
        $this->repository->save($this->createModel($future));
        $this->repository->save($this->createModelWithLastReminderDate($future));
    }

    public function setUp(): void
    {
        parent::setUp();

        $this->repository = $this->objectManager->create(SubscriptionToProductRepositoryInterface::class);
    }

    public function tearDown(): void
    {
        try {
            /** @var SubscriptionToProduct $model */
            $model = $this->objectManager->create(SubscriptionToProduct::class);
            $model->getCollection()->getConnection()->truncateTable($model->getResource()->getMainTable());
        } catch (ConnectionException $exception) {
            // ...
        }
    }

    public function testReceivesNothingWhenLastPaymentDateIsToday(): void
    {
        $today = new DateTimeImmutable();
        $future = new DateTimeImmutable('+3 day');

        $this->repository->save($this->createModel($future, $today));

        /** @var RetrieveRecordsForPrePaymentReminder $instance */
        $instance = $this->objectManager->create(RetrieveRecordsForPrePaymentReminder::class);
        $subscriptions = $instance->execute($today);

        $this->assertEquals(0, $subscriptions->getTotalCount());
    }

    /**
     * @return void
     * @see RetrieveRecordsForPrePaymentReminder::execute
     */
    public function testRetrievesNothingWhenThereShouldBeNothing(): void
    {
        $this->createDefaultModels();

        $today = new DateTimeImmutable('+1 day');

        /** @var RetrieveRecordsForPrePaymentReminder $instance */
        $instance = $this->objectManager->create(RetrieveRecordsForPrePaymentReminder::class);
        $subscriptions = $instance->execute($today);

        $this->assertEquals(0, $subscriptions->getTotalCount());
    }

    /**
     * @return void
     * @see RetrieveRecordsForPrePaymentReminder::execute
     */
    public function testRetrievesRecords(): void
    {
        $this->createDefaultModels();

        $today = new DateTimeImmutable();
        $future = new DateTimeImmutable('+3 day');

        /** @var RetrieveRecordsForPrePaymentReminder $instance */
        $instance = $this->objectManager->create(RetrieveRecordsForPrePaymentReminder::class);
        $subscriptions = $instance->execute($today);

        $this->assertEquals($future->format('Y-m-d'), $subscriptions->getItems()[0]->getNextPaymentDate());
        $this->assertEquals(2, $subscriptions->getTotalCount());
    }

    private function createModel(DateTimeImmutable $date, ?DateTimeImmutable $lastReminderDate = null): SubscriptionToProductInterface
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

    private function createModelWithLastReminderDate(DateTimeImmutable $date): SubscriptionToProductInterface
    {
        return $this->createModel(
            $date,
            $date->sub(new DateInterval('P7D'))
        );
    }
}
