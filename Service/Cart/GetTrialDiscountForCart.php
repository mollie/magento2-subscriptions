<?php
/*
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Mollie\Subscriptions\Service\Cart;

use Magento\Quote\Api\Data\CartInterface;
use Magento\Quote\Model\Quote;
use Mollie\Subscriptions\Service\Mollie\GetSelectedOption;

class GetTrialDiscountForCart
{
    public function __construct(
        private readonly GetSelectedOption $getSelectedOption,
        private readonly TrialDiscountForCartFactory $trialDiscountForCartFactory
    ) {
    }

    /**
     * @param CartInterface|Quote $cart
     */
    public function execute(CartInterface $cart): TrialDiscountForCart
    {
        $product = 0;
        $shipping = 0;
        $discount = 0;
        $itemCount = 0;
        $shippingAddress = $cart->getShippingAddress();
        foreach ($cart->getItems() ?? [] as $cartItem) {
            if (!$cartItem->getProduct()->getData('mollie_subscription_product')) {
                continue;
            }

            $metaData = $cartItem->getBuyRequest()->getData('recurring_metadata');
            if ($metaData === null || !array_key_exists('option_id', $metaData)) {
                continue;
            }

            $option = $this->getSelectedOption->execute($cartItem->getProduct(), $metaData['option_id']);
            if (!$option->getTrialDays()) {
                continue;
            }

            $itemCount++;
            $product -= $cartItem->getRowTotalInclTax();

            $shippingAddress->requestShippingRates($cartItem);
            $shipping -= $cartItem->getBaseShippingAmount();
            $discount += $cartItem->getDiscountAmount();
        }

        return $this->trialDiscountForCartFactory->create([
            'product' => $product + 0.01,
            'shipping' => $shipping,
            'discount' => $discount,
            'itemCount' => $itemCount,
        ]);
    }
}
