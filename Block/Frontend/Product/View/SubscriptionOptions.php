<?php
/*
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Mollie\Subscriptions\Block\Frontend\Product\View;

use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Catalog\Model\Product\Attribute\Source\Boolean;
use Magento\Framework\Pricing\PriceCurrencyInterface;
use Magento\Framework\Registry;
use Magento\Framework\View\Element\Template;
use Mollie\Subscriptions\Config;
use Mollie\Subscriptions\DTO\ProductSubscriptionOption;
use Mollie\Subscriptions\Service\Mollie\ParseSubscriptionOptions;

class SubscriptionOptions extends Template
{
    protected $_template = 'Mollie_Subscriptions::product/view/subscription-options.phtml';

    /**
     * @var Registry
     */
    private $registry;

    /**
     * @var Config
     */
    private $config;

    /**
     * @var ParseSubscriptionOptions
     */
    private $parseSubscriptionOptions;
    /**
     * @var PriceCurrencyInterface
     */
    private $priceCurrency;

    public function __construct(
        Template\Context $context,
        Registry $registry,
        Config $config,
        ParseSubscriptionOptions $parseSubscriptionOptions,
        PriceCurrencyInterface $priceCurrency,
        array $data = []
    ) {
        parent::__construct($context, $data);

        $this->registry = $registry;
        $this->config = $config;
        $this->parseSubscriptionOptions = $parseSubscriptionOptions;
        $this->priceCurrency = $priceCurrency;
    }

    /**
     * @return ProductSubscriptionOption[]
     */
    public function getOptions(): array
    {
        /** @var ProductInterface $product */
        $product = $this->registry->registry('current_product');

        return $this->parseSubscriptionOptions->execute($product);
    }

    public function allowOneTimePurchase(): bool
    {
        /** @var ProductInterface $product */
        $product = $this->registry->registry('current_product');

        $value = $product->getData('mollie_allow_one_time_purchase');

        if ($value == Boolean::VALUE_USE_CONFIG) {
            return $this->config->allowOneTimePurchase();
        }

        return (bool)$value;
    }

    public function showPriceInSubscriptionButton(): bool
    {
        return $this->config->showPriceInSubscriptionButton();
    }

    public function getProductPrice(): float
    {
        /** @var ProductInterface $product */
        $product = $this->registry->registry('current_product');

        if ($product->getPrice() === null) {
            return 0.0;
        }

        return (float)$product->getPrice();
    }

    public function formatPrice(float $price, bool $includeContainer = false): string
    {
        return $this->priceCurrency->format($price, $includeContainer);
    }
}
