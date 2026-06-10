<?php
/*
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Mollie\Subscriptions\Controller\Adminhtml\Subscriptions;

use Magento\Framework\App\Action\HttpGetActionInterface;
use Magento\Framework\View\Result\PageFactory;

class Transactions implements HttpGetActionInterface
{
    public function __construct(
        private readonly PageFactory $pageFactory
    ) {
    }

    public function execute()
    {
        $page = $this->pageFactory->create();
        $page->setActiveMenu('Mollie_Subscriptions::view_subscription_payments');
        $page->getConfig()->getTitle()->prepend(__('Subscription Transactions'));

        return $page;
    }
}
