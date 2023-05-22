<?php

namespace Mollie\Subscriptions\Test\Integration\Model;

use Mollie\Payment\Test\Integration\IntegrationTestCase;
use Mollie\Subscriptions\Api\Data\SubscriptionToProductInterface;
use Mollie\Subscriptions\Api\SubscriptionToProductRepositoryInterface;

class SubscriptionToProductRepositoryTest extends IntegrationTestCase
{
    public function testCanSaveAModel(): void
    {
        $model = $this->getModel();

        /** @var SubscriptionToProductRepositoryInterface $instance */
        $instance = $this->objectManager->get(SubscriptionToProductRepositoryInterface::class);

        $result = $instance->save($model);

        $this->assertNotEmpty($result->getEntityId());
        $this->assertIsNumeric($result->getEntityId());
    }

    public function testCanDeleteModel(): void
    {
        $model = $this->getModel();

        /** @var SubscriptionToProductRepositoryInterface $instance */
        $instance = $this->objectManager->get(SubscriptionToProductRepositoryInterface::class);

        $result = $instance->save($model);

        $this->assertTrue($instance->delete($result));
    }

    public function testCanDeleteBySubscriptionId(): void
    {
        $model = $this->getModel();

        /** @var SubscriptionToProductRepositoryInterface $instance */
        $instance = $this->objectManager->get(SubscriptionToProductRepositoryInterface::class);

        $result = $instance->save($model);

        $this->assertTrue(
            $instance->deleteBySubscriptionId(
                (int)$result->getCustomerId(),
                (int)$result->getSubscriptionId()
            )
        );
    }

    public function getModel(): SubscriptionToProductInterface
    {
        /** @var SubscriptionToProductInterface $model */
        $model = $this->objectManager->create(SubscriptionToProductInterface::class);
        $model->setProductId(1);
        $model->setSubscriptionId(1);
        $model->setCustomerId(1);

        return $model;
    }
}
