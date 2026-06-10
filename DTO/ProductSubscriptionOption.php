<?php
/*
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Mollie\Subscriptions\DTO;

class ProductSubscriptionOption
{
    public function __construct(
        private readonly string $identifier,
        private readonly string $title,
        private readonly string $interval_amount,
        private readonly string $interval_type,
        private readonly string $repetition_type,
        private readonly ?string $repetition_amount = null,
        private readonly ?int $trial_days = null,
        private readonly ?float $price = null
    ) {
    }

    public function getIdentifier(): string
    {
        return $this->identifier;
    }

    public function getTitle(): string
    {
        return $this->title;
    }

    public function getIntervalAmount(): string
    {
        return $this->interval_amount;
    }

    public function getIntervalType(): string
    {
        return $this->interval_type;
    }

    public function getRepetitionAmount(): ?string
    {
        return $this->repetition_amount;
    }

    public function getRepetitionType(): string
    {
        return $this->repetition_type;
    }

    public function getTrialDays(): ?int
    {
        return $this->trial_days;
    }

    public function getPrice(): ?float
    {
        return $this->price;
    }
}
