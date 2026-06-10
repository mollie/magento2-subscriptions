<?php
/*
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Mollie\Subscriptions\DTO;


use Magento\Sales\Api\Data\OrderItemInterface;

class SubscriptionOption
{
    /**
     * @var int
     */
    private $productId;

    /**
     * @var string
     */
    private $optionId;

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
    /**
     * @var OrderItemInterface|null
     */
    private $orderItem = null;

    public function __construct(
        int $productId,
        string $optionId,
        int $storeId,
        array $amount,
        string $interval,
        string $description,
        array $metadata,
        string $webhookUrl,
        \DateTimeImmutable $startDate,
        ?int $times = null
    ) {
        $this->productId = $productId;
        $this->optionId = $optionId;
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

    public function setProductId(int $productId): void
    {
        $this->productId = $productId;
    }

    public function getOptionId(): string
    {
        return $this->optionId;
    }

    public function setOptionId(string $optionId): void
    {
        $this->optionId = $optionId;
    }

    public function getStoreId(): int
    {
        return $this->storeId;
    }

    public function setStoreId(int $storeId): void
    {
        $this->storeId = $storeId;
    }

    public function getAmount(): array
    {
        return $this->amount;
    }

    public function setAmount(array $amount): void
    {
        $this->amount = $amount;
    }

    public function getInterval(): string
    {
        return $this->interval;
    }

    public function setInterval(string $interval): void
    {
        $this->interval = $interval;
    }

    public function getDescription(): string
    {
        return $this->description;
    }

    public function setDescription(string $description): void
    {
        $this->description = $description;
    }

    public function getMetadata(): array
    {
        return $this->metadata;
    }

    public function setMetadata(array $metadata): void
    {
        $this->metadata = $metadata;
    }

    public function getWebhookUrl(): string
    {
        return $this->webhookUrl;
    }

    public function setWebhookUrl(string $webhookUrl): void
    {
        $this->webhookUrl = $webhookUrl;
    }

    public function getStartDate(): \DateTimeImmutable
    {
        return $this->startDate;
    }

    public function setStartDate(\DateTimeImmutable $startDate): void
    {
        $this->startDate = $startDate;
    }

    public function getTimes(): ?int
    {
        return $this->times;
    }

    public function setTimes(?int $times): void
    {
        $this->times = $times;
    }

    public function setOrderItem(OrderItemInterface $orderItem)
    {
        $this->orderItem = $orderItem;
    }

    public function getOrderItem(): ?OrderItemInterface
    {
        return $this->orderItem;
    }

    public function toArray(): array
    {
        $output = [
            'amount' => $this->getAmount(),
            'interval' => $this->getInterval(),
            'description' => $this->getDescription(),
            'metadata' => $this->getMetadata(),
            'webhookUrl' => $this->getWebhookUrl(),
            'startDate' => $this->getStartDate()->format('Y-m-d'),
        ];

        if (!is_null($this->getTimes())) {
            $output['times'] = $this->getTimes();
        }

        return $output;
    }
}
