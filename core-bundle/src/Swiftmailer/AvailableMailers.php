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

use Contao\CoreBundle\Translation\Translator;
use Symfony\Component\HttpFoundation\RequestStack;

class AvailableMailers
{
    /**
     * @var array<MailerConfig>
     */
    private $mailers = [];

    /**
     * @var Translator
     */
    private $translator;

    /**
     * @var RequestStack
     */
    private $requestStack;

    /**
     * @var \Swift_Mailer
     */
    private $defaultMailer;

    public function __construct(Translator $translator, RequestStack $requestStack, \Swift_Mailer $defaultMailer)
    {
        $this->translator = $translator;
        $this->requestStack = $requestStack;
        $this->defaultMailer = $defaultMailer;
    }

    public function addMailer(MailerConfig $mailerConfig): void
    {
        $this->mailers[$mailerConfig->getName()] = $mailerConfig;
    }

    /**
     * Returns the available mailers as options suitable for widgets.
     *
     * @return array<string, string>
     */
    public function getMailerOptions(): array
    {
        $options = [];

        foreach (array_keys($this->mailers) as $name) {
            $options[$name] = $this->translator->trans($name, [], 'mailers') ?: $name;
        }

        return $options;
    }

    /**
     * Returns a specifc \Swift_Mailer instance based on its name.
     */
    public function getMailer(string $name): ?\Swift_Mailer
    {
        if (isset($this->mailers[$name])) {
            return $this->mailers[$name]->getMailer();
        }

        return null;
    }

    /**
     * Returns a sender address for a specific mailer, if available.
     */
    public function getSender(string $mailerName): ?string
    {
        if (isset($this->mailers[$mailerName])) {
            return $this->mailers[$mailerName]->getSender();
        }
    }

    /**
     * Returns a sender address based a mailer instance, if available.
     */
    public function getSenderByMailer(\Swift_Mailer $mailer): ?string
    {
        foreach ($this->mailers as $mailerConfig) {
            if ($mailerConfig->getMailer() === $mailer) {
                return $mailerConfig->getSender();
            }
        }

        return null;
    }

    /**
     * Returns the selected mailer for the current request.
     */
    public function getCurrentMailer(): \Swift_Mailer
    {
        $request = $this->requestStack->getCurrentRequest();

        if ($request->attributes->has('pageModel')) {
            $mailerName = $request->attributes->get('pageModel')->mailer;

            if (null !== ($currentMailer = $this->getMailer($mailerName))) {
                return $currentMailer;
            }
        }

        return $this->getDefaultMailer();
    }

    public function getDefaultMailer(): \Swift_Mailer
    {
        return $this->defaultMailer;
    }
}
