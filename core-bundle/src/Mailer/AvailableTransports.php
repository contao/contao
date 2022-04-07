<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Mailer;

use Contao\CoreBundle\ServiceAnnotation\Callback;
use Symfony\Contracts\Translation\TranslatorInterface;

class AvailableTransports
{
    /**
     * @var array<TransportConfig>
     */
    private array $transports = [];

    private ?TranslatorInterface $translator;

    public function __construct(TranslatorInterface $translator = null)
    {
        $this->translator = $translator;
    }

    public function addTransport(TransportConfig $transportConfig): void
    {
        $this->transports[$transportConfig->getName()] = $transportConfig;
    }

    /**
     * @return array<string, TransportConfig>
     */
    public function getTransports(): array
    {
        return $this->transports;
    }

    /**
     * Returns the available transports as options suitable for widgets.
     *
     * @return array<string, string>
     *
     * @Callback(table="tl_page", target="fields.mailerTransport.options")
     * @Callback(table="tl_form", target="fields.mailerTransport.options")
     */
    public function getTransportOptions(): array
    {
        $options = [];

        foreach ($this->transports as $name => $config) {
            $label = null !== $this->translator ? $this->translator->trans($name, [], 'mailer_transports') : $name;

            if (null !== ($from = $config->getFrom())) {
                $label .= ' ('.$from.')';
            }

            $options[$name] = htmlentities($label);
        }

        return $options;
    }

    /**
     * Returns a specific transport configuration by the transport name.
     */
    public function getTransport(string $name): ?TransportConfig
    {
        return $this->transports[$name] ?? null;
    }
}
