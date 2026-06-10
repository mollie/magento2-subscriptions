<?php
/*
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Mollie\Subscriptions\Service\Cart;

class TrialDiscountForCart
{
    public function __construct(
        private readonly float $product,
        private readonly float $shipping,
        private readonly float $discount,
        private readonly int $itemCount
    ) {
    }

    public function getProduct(): float
    {
        return $this->product;
    }

    public function getShipping(): float
    {
        return $this->shipping;
    }

    public function getDiscount(): float
    {
        return $this->discount;
    }

    public function getItemCount(): int
    {
        return $this->itemCount;
    }
}
