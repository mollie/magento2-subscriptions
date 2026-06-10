<?php

declare(strict_types=1);

namespace Mollie\Subscriptions\Model;

use Magento\Framework\Api\DataObjectHelper;
use Magento\Framework\Data\Collection\AbstractDb;
use Magento\Framework\Model\AbstractModel;
use Magento\Framework\Model\Context;
use Magento\Framework\Registry;
use Mollie\Subscriptions\Api\Data\SentEmailInterface;
use Mollie\Subscriptions\Api\Data\SentEmailInterfaceFactory;

class SentEmail extends AbstractModel
{
    public function __construct(
        Context $context,
        Registry $registry,
        private readonly DataObjectHelper $dataObjectHelper,
        private readonly SentEmailInterfaceFactory $sentEmailDataFactory,
        ResourceModel\SentEmail $resource,
        ?AbstractDb $resourceCollection = null,
        array $data = []
    ) {
        parent::__construct($context, $registry, $resource, $resourceCollection, $data);
    }

    public function getDataModel(): SentEmailInterface
    {
        $data = $this->getData();

        $dataObject = $this->sentEmailDataFactory->create();
        $this->dataObjectHelper->populateWithArray(
            $dataObject,
            $data,
            SentEmailInterfaceFactory::class
        );

        return $dataObject;
    }

    public function getCustomAttributes(): array
    {
        return [];
    }
}
