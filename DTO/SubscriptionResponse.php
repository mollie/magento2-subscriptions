<?php
/*
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Mollie\Subscriptions\DTO;

use DateTimeInterface;
use Magento\Customer\Api\Data\CustomerInterface;
use Mollie\Api\Resources\Subscription;

class SubscriptionResponse
{
    public function __construct(
        private readonly Subscription $subscription,
        private readonly CustomerInterface $customer,
        private readonly ?DateTimeInterface $prePaymentReminderDate = null
    ) {
    }

    /**
     * @return Subscription
     */
    public function getSubscription(): Subscription
    {
        return $this->subscription;
    }

    public function getId()
    {
        return $this->subscription->id;
    }

    public function getAmount()
    {
        return $this->subscription->amount->value;
    }

    public function getStatus()
    {
        return $this->subscription->status;
    }

    public function getDescription()
    {
        return $this->subscription->description;
    }

    /**
     * @return string|null
     */
    public function getParentId()
    {
        return $this->subscription->metadata && isset($this->subscription->metadata->parent_id) ?
            $this->subscription->metadata->parent_id :
            null;
    }

    public function getCreatedAt()
    {
        return $this->subscription->createdAt;
    }

    public function toArray()
    {
        $prePaymentReminderDate = null;
        if ($this->prePaymentReminderDate) {
            $prePaymentReminderDate = $this->prePaymentReminderDate->format('Y-m-d');
        }

        return [
            'id' => $this->subscription->id,
            'customer_id' => $this->subscription->customerId,
            'customer_name' => $this->getFullName(),
            'amount' => $this->subscription->amount->value,
            'mode' => $this->subscription->mode,
            'next_payment_date' => $this->subscription->nextPaymentDate,
            'status' => $this->subscription->status,
            'description' => $this->subscription->description,
            'created_at' => $this->subscription->createdAt,
            'prepayment_reminder_date' => $prePaymentReminderDate,
        ];
    }

    /**
     * @return string
     */
    private function getFullName(): string
    {
        /** @var array $name */
        $name = array_filter([
            $this->customer->getFirstname(),
            $this->customer->getMiddlename(),
            $this->customer->getLastname(),
        ]);

        return implode(' ', $name);
    }
}
