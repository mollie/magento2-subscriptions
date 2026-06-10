<?php
/*
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Mollie\Subscriptions\Service\Email;

use Laminas\Mime\Message;
use Magento\Framework\Mail\EmailMessage;
use Magento\Framework\Mail\TransportInterface;
use Mollie\Subscriptions\Api\Data\SentEmailInterface;
use Mollie\Subscriptions\Api\Data\SentEmailInterfaceFactory;
use Mollie\Subscriptions\Api\SentEmailRepositoryInterface;

class LogEmail
{
    public function __construct(
        private readonly SentEmailRepositoryInterface $sentEmailRepository,
        private readonly SentEmailInterfaceFactory $sentEmailInterfaceFactory
    ) {
    }

    public function execute(TransportInterface $transport): void
    {
        /** @var SentEmailInterface $model */
        $model = $this->sentEmailInterfaceFactory->create();

        /** @var EmailMessage $message */
        $message = $transport->getMessage();
        $model->setSubject($message->getSubject());
        $model->setRecipients($this->addressesToString($message->getTo()));
        $model->setSender($this->addressesToString($message->getFrom()));
        $model->setSentAt(date('Y-m-d H:i:s'));

        $this->sentEmailRepository->save($model);
    }

    /**
     * @param \Magento\Framework\Mail\Address[] $addresses
     * @return string
     */
    private function addressesToString(array $addresses): string
    {
        $output = [];
        foreach ($addresses as $address) {
            $output[] = $address->getName() . ' (' . $address->getEmail() . ')';
        }

        return implode(', ', $output);
    }
}
