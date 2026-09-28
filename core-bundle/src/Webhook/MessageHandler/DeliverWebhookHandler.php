<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Webhook\MessageHandler;

use Contao\CoreBundle\Webhook\Message\DeliverWebhookMessage;
use Contao\CoreBundle\Webhook\SecretEncryption;
use Contao\CoreBundle\Webhook\WebhookRepository;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpClient\HttpOptions;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Component\Messenger\Exception\UnrecoverableMessageHandlingException;
use Symfony\Component\RemoteEvent\RemoteEvent;
use Symfony\Component\Webhook\Server\HeadersConfigurator;
use Symfony\Component\Webhook\Server\HeaderSignatureConfigurator;
use Symfony\Component\Webhook\Server\JsonBodyConfigurator;
use Symfony\Component\Webhook\Server\NativeJsonPayloadSerializer;
use Symfony\Contracts\HttpClient\HttpClientInterface;

#[AsMessageHandler]
final class DeliverWebhookHandler
{
    public function __construct(
        private readonly WebhookRepository $repository,
        private readonly SecretEncryption $encryption,
        private readonly HttpClientInterface $client,
        private readonly LoggerInterface|null $logger = null,
        private readonly int $connectTimeout = 10,
        private readonly int $totalTimeout = 15,
        private readonly int $maxRedirects = 0,
    ) {
    }

    public function __invoke(DeliverWebhookMessage $message): void
    {
        $delivery = $this->repository->getOutgoingSubscriptionById($message->subscriptionId);

        if (null === $delivery) {
            return;
        }

        if (null === $delivery['url'] || !(bool) $delivery['enabled'] || null === $delivery['secret']) {
            throw new UnrecoverableMessageHandlingException('Subscription unavailable.');
        }

        $url = (string) $delivery['url'];
        $parts = parse_url($url);

        if (false === $parts || 'https' !== strtolower($parts['scheme'] ?? '') || isset($parts['user']) || isset($parts['pass'])) {
            throw new UnrecoverableMessageHandlingException('Destination URL must be HTTPS and contain no credentials.');
        }

        $status = $this->send($message, $delivery, $url);

        if ($status >= 200 && $status < 300) {
            $this->logger?->info('Webhook delivery succeeded.', ['subscription_id' => $message->subscriptionId, 'status' => $status]);

            return;
        }

        $error = \sprintf('Remote server returned HTTP %d.', $status);

        if ($status >= 400 && $status < 500 && 429 !== $status) {
            throw new UnrecoverableMessageHandlingException($error);
        }

        throw new \RuntimeException($error);
    }

    private function send(DeliverWebhookMessage $message, array $delivery, string $url): int
    {
        try {
            $secret = '' === $delivery['secret'] ? '' : $this->encryption->decrypt($delivery['secret']);
        } catch (\RuntimeException $exception) {
            throw new UnrecoverableMessageHandlingException('The subscription secret cannot be decrypted.', previous: $exception);
        }
        $event = new RemoteEvent($message->name, $message->externalId, $message->payload);
        $options = new HttpOptions();
        $headers = new HeadersConfigurator();
        $body = new JsonBodyConfigurator(new NativeJsonPayloadSerializer());
        $headers->configure($event, $secret, $options);
        $body->configure($event, $secret, $options);

        if ('' !== $secret) {
            new HeaderSignatureConfigurator()->configure($event, $secret, $options);
        }

        $response = $this->client->withOptions(['timeout' => $this->connectTimeout, 'max_redirects' => $this->maxRedirects, 'max_duration' => $this->totalTimeout])->request('POST', $url, $options->toArray());

        try {
            $response->getContent(false);

            return $response->getStatusCode();
        } finally {
            $response->cancel();
        }
    }
}
