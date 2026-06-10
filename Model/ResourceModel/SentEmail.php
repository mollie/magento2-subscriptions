<?php

declare(strict_types=1);

namespace Mollie\Subscriptions\Model\ResourceModel;

use Magento\Framework\Model\ResourceModel\Db\AbstractDb;

class SentEmail extends AbstractDb
{
    public const MAIN_TABLE = 'mollie_subscriptions_sentemail';

    public const ID_FIELD_NAME = 'entity_id';

    protected function _construct(): void
    {
        $this->_init(self::MAIN_TABLE, self::ID_FIELD_NAME);
    }
}
