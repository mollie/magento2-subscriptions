<?php
/*
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Mollie\Subscriptions\Service\Mollie;

use Mollie\Api\Resources\Subscription;
use Mollie\Subscriptions\Api\SubscriptionToProductRepositoryInterface;

class UpdateNextPaymentDate
{
    /**
     * @var SubscriptionToProductRepositoryInterface
     */
    private $subscriptionToProductRepository;

    public function __construct(
        SubscriptionToProductRepositoryInterface $subscriptionToProductRepository
    ) {
        $this->subscriptionToProductRepository = $subscriptionToProductRepository;
    }

    public function execute(Subscription $subscription): void
    {
        $row = $this->subscriptionToProductRepository->getBySubscriptionId($subscription->id);
        $row->setNextPaymentDate($subscription->nextPaymentDate);
        $this->subscriptionToProductRepository->save($row);
    }
}
