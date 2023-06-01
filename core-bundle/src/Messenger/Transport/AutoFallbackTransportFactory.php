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

use Contao\CoreBundle\Messenger\AutoFallbackNotifier;
use Psr\Container\ContainerInterface;
use Symfony\Component\Messenger\Exception\InvalidArgumentException;
use Symfony\Component\Messenger\Transport\Serialization\SerializerInterface;
use Symfony\Component\Messenger\Transport\TransportFactoryInterface;
use Symfony\Component\Messenger\Transport\TransportInterface;

class AutoFallbackTransportFactory implements TransportFactoryInterface
{
    public function __construct(
        private readonly AutoFallbackNotifier $autoFallbackNotifier,
        private readonly ContainerInterface $messengerTransportLocator,
    ) {
    }

    public function createTransport(string $dsn, array $options, SerializerInterface $serializer): TransportInterface
    {
        if (!$parsedUrl = parse_url($dsn)) {
            throw new InvalidArgumentException(sprintf('The given Auto Fallback DSN "%s" is invalid.', $dsn));
        }

        parse_str($parsedUrl['query'] ?? '', $parsedQuery);

        $self = $parsedUrl['host'] ?? '';
        $target = $parsedQuery['target'] ?? '';
        $fallback = $parsedQuery['fallback'] ?? '';

        if (!$this->messengerTransportLocator->has($self)) {
            throw new InvalidArgumentException(sprintf('The given Auto Fallback Transport self "%s" is invalid.', $self));
        }

        if (!$this->messengerTransportLocator->has($target)) {
            throw new InvalidArgumentException(sprintf('The given Auto Fallback Transport target "%s" is invalid.', $target));
        }

        if (!$this->messengerTransportLocator->has($fallback)) {
            throw new InvalidArgumentException(sprintf('The given Auto Fallback Transport fallback "%s" is invalid.', $fallback));
        }

        return new AutoFallbackTransport(
            $this->autoFallbackNotifier,
            $self,
            $this->messengerTransportLocator->get($target),
            $this->messengerTransportLocator->get($fallback),
        );
    }

    public function supports(string $dsn, array $options): bool
    {
        return str_starts_with($dsn, 'contao-auto-fallback://');
    }
}
