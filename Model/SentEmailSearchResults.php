<?php

declare(strict_types=1);

namespace Mollie\Subscriptions\Model;

use Magento\Framework\Api\SearchResults;
use Mollie\Subscriptions\Api\Data\SentEmailSearchResultsInterface;

class SentEmailSearchResults extends SearchResults implements SentEmailSearchResultsInterface
{
    public function getItems(): array
    {
        return parent::getItems();
    }
}
