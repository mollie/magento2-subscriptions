<?php

declare(strict_types=1);

namespace Mollie\Subscriptions\Service\Mollie;

class OrderHasTrialProductResult
{
    public function __construct(
        private readonly bool $outcome,
        private readonly float $trialAmountTotal
    ) {
    }

    public function getOutcome(): bool
    {
        return $this->outcome;
    }

    public function getTrialAmountTotal(): float
    {
        return $this->trialAmountTotal;
    }
}
