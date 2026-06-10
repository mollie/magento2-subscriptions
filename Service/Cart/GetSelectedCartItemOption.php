<?php

declare(strict_types=1);

namespace Mollie\Subscriptions\Service\Cart;

use Magento\Framework\Serialize\SerializerInterface;
use Magento\Quote\Api\Data\CartItemInterface;
use Mollie\Subscriptions\DTO\ProductSubscriptionOption;
use Mollie\Subscriptions\Service\Mollie\GetSelectedOption;

class GetSelectedCartItemOption
{
    public function __construct(
        private readonly SerializerInterface $serializer,
        private readonly GetSelectedOption $getSelectedOption
    ) {
    }

    public function execute(CartItemInterface $item): ?ProductSubscriptionOption
    {
        $options = $item->getOptionsByCode();
        if (!isset($options['info_buyRequest'])) {
            return null;
        }

        $json = $options['info_buyRequest']->getData('value');
        $data = $this->serializer->unserialize($json);

        if (!array_key_exists('recurring_metadata', $data) || !array_key_exists('option_id', $data['recurring_metadata'])) {
            return null;
        }

        return $this->getSelectedOption->execute($item->getProduct(), $data['recurring_metadata']['option_id']);
    }
}
