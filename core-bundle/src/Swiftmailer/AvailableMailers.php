<?php

namespace Contao\CoreBundle\Swiftmailer;

use Contao\CoreBundle\Translation\Translator;
use Symfony\Component\HttpFoundation\RequestStack;

class AvailableMailers
{
    /**
     * @var array<string, \Swift_Mailer>
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

    /**
     * Sets the available mailers.
     * 
     * @param array<string, \Swift_Mailer> $mailers
     */
    public function setMailers(array $mailers): void
    {
        $this->mailers = $mailers;
    }

    /**
     * Returns the available mailers as options suitable for widgets.
     *
     * @return array<string, string>
     */
    public function getMailerOptions(): array
    {
        $options = [];

        foreach ($this->mailers as $name => $mailer) {
            $options[$name] = $this->translator->trans($name, [], 'mailers') ?: $name;
        }

        return $options;
    }

    /**
     * Returns a specifc \Swift_Mailer instance based on its name.
     */
    public function getMailer(string $name): ?\Swift_Mailer
    {
        return $this->mailers[$name] ?? null;
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
