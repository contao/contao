<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Swiftmailer;

final class MailerConfig
{
    /**
     * @var string
     */
    private $name;

    /**
     * @var \Swift_Mailer
     */
    private $mailer;

    /**
     * @var string
     */
    private $sender;

    public function __construct(string $name, \Swift_Mailer $mailer, string $sender = null)
    {
        $this->name = $name;
        $this->mailer = $mailer;
        $this->sender = $sender;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getMailer(): \Swift_Mailer
    {
        return $this->mailer;
    }

    public function getSender(): ?string
    {
        return $this->sender;
    }
}
