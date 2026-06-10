<?php
/*
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Mollie\Subscriptions\DTO;

class SubscriptionOption
{
    public function __construct(
        private readonly int $productId,
        private readonly string $optionId,
        private readonly int $storeId,
        private readonly array $amount,
        private readonly string $interval,
        private readonly string $description,
        private readonly array $metadata,
        private readonly string $webhookUrl,
        private readonly \DateTimeImmutable $startDate,
        private readonly ?int $times = null
    ) {
    }

    public function getProductId(): int
    {
        return $this->productId;
    }

    public function getOptionId(): string
    {
        return $this->optionId;
    }

    public function getStoreId(): int
    {
        return $this->storeId;
    }

    public function toArray(): array
    {
        $output = [
            'amount' => $this->amount,
            'interval' => $this->interval,
            'description' => $this->description,
            'metadata' => $this->metadata,
            'webhookUrl' => $this->webhookUrl,
            'startDate' => $this->startDate->format('Y-m-d'),
        ];

        if ($this->times) {
            $output['times'] = $this->times;
        }

        return $output;
    }
}
