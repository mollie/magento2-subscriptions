<?php
/*
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Mollie\Subscriptions\Model\Carrier;

use Magento\Quote\Model\Quote\Address\RateRequest;
use Magento\Shipping\Model\Carrier\AbstractCarrier;
use Magento\Shipping\Model\Carrier\CarrierInterface;

class SubscriptionShipping extends AbstractCarrier implements CarrierInterface
{
    public const CARRIER_CODE = 'mollie_subscriptions';

    protected $_code = self::CARRIER_CODE;

    protected $_isFixed = true;

    /**
     * This carrier is not available during checkout. It is only used as a fallback
     * when no other shipping methods produce rates for subscription orders.
     */
    public function collectRates(RateRequest $request)
    {
        return false;
    }

    public function getAllowedMethods(): array
    {
        return ['fallback_shipping' => $this->getConfigData('name') ?: 'Mollie Subscriptions Fallback Shipping'];
    }
}
