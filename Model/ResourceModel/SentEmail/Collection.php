<?php

declare(strict_types=1);

namespace Mollie\Subscriptions\Model\ResourceModel\SentEmail;

use Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection;
use Mollie\Subscriptions\Model\SentEmail;

class Collection extends AbstractCollection
{
    protected function _construct(): void
    {
        $this->_init(SentEmail::class, \Mollie\Subscriptions\Model\ResourceModel\SentEmail::class);
    }
}
