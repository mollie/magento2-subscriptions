<?php
/*
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Mollie\Subscriptions\Controller\Adminhtml\Subscriptions;

use Magento\Framework\App\Action\HttpGetActionInterface;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\View\Result\PageFactory;

class UpdatePrice implements HttpGetActionInterface
{
    public function __construct(
        private readonly RequestInterface $request,
        private readonly PageFactory $pageFactory
    ) {
    }

    public function execute()
    {
        $customerId = $this->request->getParam('customer_id');
        $subscriptionId = $this->request->getParam('subscription_id');

        if (!$customerId || !$subscriptionId) {
            throw new LocalizedException(__('Customer ID and subscription ID are required.'));
        }

        $page = $this->pageFactory->create();
        $page->setActiveMenu('Mollie_Subscriptions::view_subscriptions');
        $page->getConfig()->getTitle()->prepend(__('Update Subscription Price'));

        return $page;
    }
}
