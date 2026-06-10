<?php

declare(strict_types=1);

namespace Mollie\Subscriptions\Service\Mollie;

use Magento\Framework\Mail\Template\SenderResolverInterface;
use Magento\Framework\Mail\Template\TransportBuilder;
use Magento\Framework\UrlInterface;
use Mollie\Subscriptions\Config;
use Mollie\Subscriptions\Service\Email\LogEmail;

class SendAdminNotification
{
    public function __construct(
        private readonly Config $config,
        private readonly TransportBuilder $transportBuilder,
        private readonly SenderResolverInterface $senderResolver,
        private readonly UrlInterface $urlInterface,
        private readonly LogEmail $logEmail
    ) {
    }

    public function send(string $id, \Throwable $exception): void
    {
        if (!$this->config->isErrorEmailEnabled()) {
            return;
        }

        $url = $this->urlInterface->getCurrentUrl();
        $sender = $this->senderResolver->resolve($this->config->errorEmailSender());
        $receiver = $this->senderResolver->resolve($this->config->errorEmailReceiver());

        $templateId = $this->config->subscriptionErrorAdminNotificationTemplate();
        $builder = $this->transportBuilder->setTemplateIdentifier($templateId);
        $builder->setTemplateOptions(['area' => 'frontend', 'store' => $this->config->getStore()->getId()]);

        $builder->setFromByScope($sender);
        $builder->setTemplateVars([
            'id' => $id,
            'url' => $url,
            'error' => $exception->__toString(),
        ]);

        $builder->addTo($receiver['email'], $receiver['name']);

        $transport = $builder->getTransport();
        $this->logEmail->execute($transport);
        $transport->sendMessage();
    }
}
