<?php
/*
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Mollie\Subscriptions\Observer\ViewBlockAbstractToHtmlAfter;

use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Framework\DomDocument\DomDocumentFactory;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\Registry;
use Magento\Framework\View\Element\Template;
use Mollie\Subscriptions\Block\Frontend\Product\View\SubscriptionOptions;
use Mollie\Payment\Config;

class ChangeAddToCartText implements ObserverInterface
{
    /**
     * @var DomDocumentFactory
     */
    private $domDocumentFactory;

    /**
     * @var Config
     */
    private $config;

    /**
     * @var Registry
     */
    private $registry;

    public function __construct(
        Config $config,
        DomDocumentFactory $domDocumentFactory,
        Registry $registry
    ) {
        $this->domDocumentFactory = $domDocumentFactory;
        $this->config = $config;
        $this->registry = $registry;
    }

    public function execute(Observer $observer)
    {
        $block = $observer->getData('block');
        if (!$block instanceof Template ||
            !in_array($block->getNameInLayout(), ['product.info.addtocart', 'product.info.addtocart.bundle'])
        ) {
            return;
        }

        if (!$this->getProduct() || !$this->getProduct()->getData('mollie_subscription_product')) {
            return;
        }

        $transport = $observer->getData('transport');
        $html = $transport->getData('html');

        $document = $this->domDocumentFactory->create();
        $document->preserveWhiteSpace = false;
        $document->formatOutput = true;

        try {
            libxml_use_internal_errors(true);
            $load = $document->loadHTML($html, LIBXML_HTML_NODEFDTD | LIBXML_HTML_NOIMPLIED);
            libxml_clear_errors();

            if (!$load) {
                return;
            }
        } catch (\Throwable $exception) {
            $this->config->addToLog(
                'error',
                __('Exception while adding the subscription buttons:') .
                PHP_EOL .
                (string)$exception
            );

            return;
        }

        /** @var \DOMElement $button */
        $button = $document->getElementById('product-addtocart-button');
        if (!$button) {
            return;
        }

        $subscriptionOptionsBlock = $block->getLayout()->createBlock(SubscriptionOptions::class)->toHtml();
        $newHtml = $this->domDocumentFactory->create();
        $newHtml->preserveWhiteSpace = false;
        $newHtml->formatOutput = true;
        $newHtml->loadHTML($subscriptionOptionsBlock, LIBXML_HTML_NODEFDTD | LIBXML_HTML_NOIMPLIED);

        // Import our HTML before the regular "add to cart button".
        $imported = $document->importNode($newHtml->documentElement, true);
        $button->parentNode->insertBefore($imported, $button);

        // Remove the button
        $button->parentNode->removeChild($button);

        $transport->setData('html', $document->saveHTML());
    }

    public function getProduct(): ?ProductInterface
    {
        return $this->registry->registry('product');
    }
}
