<?php
/**
 * Copyright © Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Mollie\Subscriptions\Controller\Adminhtml\Selftest;

use Magento\Backend\App\Action;
use Magento\Framework\Controller\Result\Json;
use Magento\Framework\Controller\Result\JsonFactory;
use Mollie\Subscriptions\Api\Selftest\RepositoryInterface as SelftestRepository;

/**
 * Class index
 *
 * AJAX controller to is provided check result
 */
class Index extends Action
{
    public function __construct(
        Action\Context $context,
        private readonly JsonFactory $resultJsonFactory,
        private readonly SelftestRepository $selftestRepository
    ) {
        parent::__construct($context);
    }

    public function execute(): Json
    {
        $resultJson = $this->resultJsonFactory->create();
        $result = $this->selftestRepository->test();
        return $resultJson->setData(['result' => $result]);
    }
}
