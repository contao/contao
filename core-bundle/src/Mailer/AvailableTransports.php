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

use Contao\CoreBundle\DependencyInjection\Attribute\AsCallback;
use Symfony\Contracts\Translation\TranslatorInterface;

class AvailableTransports
{
    /**
     * @var array<TransportConfig>
     */
    private array $transports = [];

    public function __construct(private readonly TranslatorInterface|null $translator = null)
    {
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
     */
    #[AsCallback(table: 'tl_page', target: 'fields.mailerTransport.options')]
    #[AsCallback(table: 'tl_form', target: 'fields.mailerTransport.options')]
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
    public function getTransport(string $name): TransportConfig|null
    {
        return $this->transports[$name] ?? null;
    }
}
