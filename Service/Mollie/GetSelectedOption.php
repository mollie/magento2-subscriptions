<?php

declare(strict_types=1);

namespace Mollie\Subscriptions\Service\Mollie;

use Magento\Catalog\Api\Data\ProductInterface;
use Mollie\Subscriptions\DTO\ProductSubscriptionOption;
use Mollie\Subscriptions\DTO\ProductSubscriptionOptionFactory;

class GetSelectedOption
{
    public function __construct(
        private readonly ParseSubscriptionOptions $parseSubscriptionOptions,
        private readonly ProductSubscriptionOptionFactory $productSubscriptionOptionFactory
    ) {
    }

    public function execute(ProductInterface $product, string $optionId): ProductSubscriptionOption
    {
        if ($optionId == 'onetimepurchase') {
            return $this->productSubscriptionOptionFactory->create([
                'identifier' => 'onetimepurchase',
                'title' => __('One Time Purchase'),
                'interval_amount' => '',
                'interval_type' => '',
                'repetition_type' => 'onetime',
            ]);
        }

        $options = $this->parseSubscriptionOptions->execute($product);
        foreach ($options as $option) {
            if ($option->getIdentifier() === $optionId) {
                return $option;
            }
        }

        throw new \Exception(sprintf('No option with ID %s available', $optionId));
    }
}
