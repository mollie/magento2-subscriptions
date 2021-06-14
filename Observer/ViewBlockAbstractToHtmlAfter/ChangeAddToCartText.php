<?php
/*
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Mollie\Subscriptions\Observer\ViewBlockAbstractToHtmlAfter;

use Magento\Catalog\Block\Product\View;
use Magento\Framework\DomDocument\DomDocumentFactory;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Mollie\Subscriptions\Config;

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

    public function __construct(
        Config $config,
        DomDocumentFactory $domDocumentFactory
    ) {
        $this->domDocumentFactory = $domDocumentFactory;
        $this->config = $config;
    }

    public function execute(Observer $observer)
    {
        $block = $observer->getData('block');
        if (!$block instanceof View ||
            !in_array($block->getNameInLayout(), ['product.info.addtocart', 'product.info.addtocart.bundle'])
        ) {
            return;
        }

        if (!$block->getProduct()->getData('mollie_subscription_product')) {
            return;
        }

        $transport = $observer->getData('transport');
        $html = $transport->getData('html');

        $document = $this->domDocumentFactory->create();
        $document->preserveWhiteSpace = false;
        $document->formatOutput = true;

        $document->loadHTML($html, LIBXML_HTML_NODEFDTD | LIBXML_HTML_NOIMPLIED);

        $button = $document->getElementById('product-addtocart-button');
        if (!$button) {
            return;
        }

        $text = $this->config->getAddToCartText();
        $button->setAttribute('title', ($text));

        $button->childNodes->item(1)->textContent = $block->escapeHtml($text);

        $transport->setData('html', $document->saveHTML());
    }
}
