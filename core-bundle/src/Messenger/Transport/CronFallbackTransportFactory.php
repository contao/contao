<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Messenger\Transport;

use Contao\CoreBundle\Cron\Cron;
use Psr\Container\ContainerInterface;
use Symfony\Component\Messenger\Exception\InvalidArgumentException;
use Symfony\Component\Messenger\Transport\Serialization\SerializerInterface;
use Symfony\Component\Messenger\Transport\TransportFactoryInterface;
use Symfony\Component\Messenger\Transport\TransportInterface;

class CronFallbackTransportFactory implements TransportFactoryInterface
{
    public function __construct(private Cron $cron, private ContainerInterface $messengerTransportLocator)
    {
    }

    public function createTransport(string $dsn, array $options, SerializerInterface $serializer): TransportInterface
    {
        if (false === $parsedUrl = parse_url($dsn)) {
            throw new InvalidArgumentException(sprintf('The given Contao Cron Fallback DSN "%s" is invalid.', $dsn));
        }

        $target = isset($parsedUrl['path']) ? substr($parsedUrl['path'], \strlen('contao_cron_fallback://')) : '';
        parse_str($parsedUrl['query'] ?? '', $parsedQuery);
        $fallback = $parsedQuery['fallback'] ?? '';

        if (!$this->messengerTransportLocator->has($target)) {
            throw new InvalidArgumentException(sprintf('The given Contao Cron Fallback Transport target "%s" is invalid.', $target));
        }

        if (!$this->messengerTransportLocator->has($fallback)) {
            throw new InvalidArgumentException(sprintf('The given Contao Cron Fallback Transport fallback "%s" is invalid.', $fallback));
        }

        return new CronFallbackTransport(
            $this->cron,
            $this->messengerTransportLocator->get($target),
            $this->messengerTransportLocator->get($fallback),
        );
    }

    public function supports(string $dsn, array $options): bool
    {
        return str_starts_with($dsn, 'contao_cron_fallback://');
    }
}
