<?php
/*
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Mollie\Subscriptions\DTO;


class SubscriptionOption
{
    /**
     * @var int
     */
    private $productId;

    /**
     * @var int
     */
    private $storeId;

    /**
     * @var array
     */
    private $amount;

    /**
     * @var string
     */
    private $interval;

    /**
     * @var string
     */
    private $description;

    /**
     * @var array
     */
    private $metadata;

    /**
     * @var string
     */
    private $webhookUrl;

    /**
     * @var \DateTimeImmutable
     */
    private $startDate;

    /**
     * @var int|null
     */
    private $times;

    public function __construct(
        int $productId,
        int $storeId,
        array $amount,
        string $interval,
        string $description,
        array $metadata,
        string $webhookUrl,
        \DateTimeImmutable $startDate,
        int $times = null
    ) {
        $this->productId = $productId;
        $this->storeId = $storeId;
        $this->amount = $amount;
        $this->interval = $interval;
        $this->description = $description;
        $this->metadata = $metadata;
        $this->webhookUrl = $webhookUrl;
        $this->startDate = $startDate;
        $this->times = $times;
    }

    public function getProductId(): int
    {
        return $this->productId;
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
