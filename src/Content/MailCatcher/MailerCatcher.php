<?php

namespace SwagTestEnvironment\Content\MailCatcher;

use Shopware\Core\Content\Mail\Service\AbstractMailSender;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\Plugin\Exception\DecorationPatternException;
use Symfony\Component\Mailer\Envelope;
use Symfony\Component\Mime\RawMessage;

class MailerCatcher extends AbstractMailSender
{
    private EntityRepository $mailRepository;

    public function __construct(EntityRepository $mailRepository)
    {
        $this->mailRepository = $mailRepository;
    }

    public function send(RawMessage $email, Envelope $envelope = null): void
    {
        $this->mailRepository->create([
            [
                'sender' => [$email->getFrom()[0]->getAddress() => $email->getFrom()[0]->getName()],
                'receiver' => $this->convertAddress($email->getTo()),
                'subject' => $email->getSubject(),
                'plainText' => nl2br($email->getTextBody()),
                'htmlText' => $email->getHtmlBody(),
                'eml' => $email->toString(),
            ]
        ], Context::createDefaultContext());
    }

    private function convertAddress(array $addresses): array
    {
        $list = [];

        foreach ($addresses as $address) {
            $list[$address->getAddress()] = $address->getName();
        }

        return $list;
    }

    public function getDecorated(): AbstractMailSender
    {
        throw new DecorationPatternException(self::class);
    }
}
