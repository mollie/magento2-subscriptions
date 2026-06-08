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
    /**
     * @var RequestInterface
     */
    private $request;
    /**
     * @var PageFactory
     */
    private $pageFactory;

    public function __construct(
        RequestInterface $request,
        PageFactory $pageFactory
    )
    {
        $this->pageFactory = $pageFactory;
        $this->request = $request;
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
