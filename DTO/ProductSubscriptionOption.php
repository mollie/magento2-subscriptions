<?php
/*
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Mollie\Subscriptions\DTO;

class ProductSubscriptionOption
{
    /**
     * @var string
     */
    private $identifier;

    /**
     * @var string
     */
    private $title;

    /**
     * @var string
     */
    private $interval_amount;

    /**
     * @var string
     */
    private $interval_type;

    /**
     * @var string
     */
    private $repetition_amount;

    /**
     * @var string
     */
    private $repetition_type;

    public function __construct(
        string $identifier,
        string $title,
        string $interval_amount,
        string $interval_type,
        string $repetition_type,
        string $repetition_amount = null
    ) {
        $this->identifier = $identifier;
        $this->title = $title;
        $this->interval_amount = $interval_amount;
        $this->interval_type = $interval_type;
        $this->repetition_amount = $repetition_amount;
        $this->repetition_type = $repetition_type;
    }

    /**
     * @return string
     */
    public function getIdentifier(): string
    {
        return $this->identifier;
    }

    /**
     * @return string
     */
    public function getTitle(): string
    {
        return $this->title;
    }

    /**
     * @return string
     */
    public function getIntervalAmount(): string
    {
        return $this->interval_amount;
    }

    /**
     * @return string
     */
    public function getIntervalType(): string
    {
        return $this->interval_type;
    }

    /**
     * @return string
     */
    public function getRepetitionAmount(): ?string
    {
        return $this->repetition_amount;
    }

    /**
     * @return string
     */
    public function getRepetitionType(): string
    {
        return $this->repetition_type;
    }
}
