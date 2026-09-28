<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Webhook;

use Psr\Container\ContainerInterface;
use Symfony\Component\Webhook\Client\RequestParserInterface;

final class WebhookReceiverRegistry
{
    /**
     * @param array<string, array{consumer: string, parser: string}> $receivers
     */
    public function __construct(
        private readonly array $receivers,
        private readonly ContainerInterface $services,
    ) {
    }

    /**
     * @return array{consumer: string, parser: string}|null
     */
    public function get(string $name): array|null
    {
        return $this->receivers[$name] ?? null;
    }

    public function getParser(string $name): RequestParserInterface
    {
        $receiver = $this->receivers[$name] ?? null;

        if (null === $receiver) {
            throw new \LogicException(\sprintf('Webhook receiver "%s" is not registered.', $name));
        }

        $parser = $this->services->get($receiver['parser']);

        if (!$parser instanceof RequestParserInterface) {
            throw new \LogicException(\sprintf('The parser for webhook receiver "%s" has an invalid service.', $name));
        }

        return $parser;
    }

    public function getConsumer(string $name): WebhookConsumerInterface
    {
        $receiver = $this->receivers[$name] ?? null;

        if (null === $receiver) {
            throw new \LogicException(\sprintf('Webhook receiver "%s" is not registered.', $name));
        }

        $consumer = $this->services->get($receiver['consumer']);

        if (!$consumer instanceof WebhookConsumerInterface) {
            throw new \LogicException(\sprintf('Webhook receiver "%s" has an invalid consumer service.', $name));
        }

        return $consumer;
    }

    /**
     * @return array<string, array{consumer: string, parser: string}>
     */
    public function all(): array
    {
        return $this->receivers;
    }
}
