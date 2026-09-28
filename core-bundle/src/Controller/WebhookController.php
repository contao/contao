<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Controller;

use Contao\CoreBundle\Webhook\Message\ConsumeWebhookMessage;
use Contao\CoreBundle\Webhook\SecretEncryption;
use Contao\CoreBundle\Webhook\WebhookReceiverRegistry;
use Contao\CoreBundle\Webhook\WebhookRepository;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\RateLimiter\RateLimiterFactory;
use Symfony\Component\RemoteEvent\RemoteEvent;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Webhook\Exception\RejectWebhookException;

final class WebhookController
{
    public function __construct(
        private readonly WebhookRepository $repository,
        private readonly WebhookReceiverRegistry $receivers,
        private readonly SecretEncryption $encryption,
        private readonly MessageBusInterface $messageBus,
        private readonly RateLimiterFactory $rateLimiter,
        private readonly LoggerInterface|null $logger = null,
        private readonly int $requestBodyLimit = 1048576,
    ) {
    }

    #[Route('/_contao/webhook/{endpointToken}', name: 'contao_webhook_incoming', methods: ['POST'], defaults: ['_token_check' => false], requirements: ['endpointToken' => '[A-Za-z0-9_-]{32,64}'])]
    public function __invoke(Request $request, string $endpointToken): Response
    {
        $contentLength = (int) $request->headers->get('Content-Length', '0');

        if ($contentLength > $this->requestBodyLimit) {
            return new Response('', Response::HTTP_REQUEST_ENTITY_TOO_LARGE);
        }

        $content = $request->getContent(true);
        $body = \is_resource($content) ? stream_get_contents($content, $this->requestBodyLimit + 1) : $content;

        if (false === $body || \strlen($body) > $this->requestBodyLimit) {
            return new Response('', Response::HTTP_REQUEST_ENTITY_TOO_LARGE);
        }

        $request->initialize(
            $request->query->all(),
            $request->request->all(),
            $request->attributes->all(),
            $request->cookies->all(),
            $request->files->all(),
            $request->server->all(),
            $body,
        );

        $endpoint = $this->repository->findEnabledReceiver($endpointToken);

        $rateLimitKey = null === $endpoint ? 'unknown' : (string) $endpoint['id'];
        $limiter = $this->rateLimiter->create(hash('sha256', $rateLimitKey.'|'.($request->getClientIp() ?? 'unknown')));

        if (!$limiter->consume()->isAccepted()) {
            return new Response('', Response::HTTP_TOO_MANY_REQUESTS);
        }

        if (null === $endpoint || null === $this->receivers->get($endpoint['receiver'])) {
            return new Response('', Response::HTTP_NOT_FOUND);
        }

        $parser = null;

        try {
            $parser = $this->receivers->getParser($endpoint['receiver']);
            $secret = '' === $endpoint['secret'] ? '' : $this->encryption->decrypt($endpoint['secret']);
            $remoteEvents = $parser->parse($request, $secret);
        } catch (RejectWebhookException|HttpExceptionInterface $e) {
            $this->logger?->error('Incoming webhook request rejected. ('.$e->getMessage().')', ['endpoint' => $endpoint['id']]);

            return $parser->createRejectedResponse('Webhook rejected.');
        } catch (\Throwable $e) {
            $this->logger?->error('Incoming webhook request could not be parsed. ('.$e->getMessage().')', ['endpoint' => $endpoint['id']]);

            return new Response('', null === $parser ? Response::HTTP_INTERNAL_SERVER_ERROR : Response::HTTP_BAD_REQUEST);
        }

        if (null === $remoteEvents) {
            return $parser->createSuccessfulResponse($request);
        }

        $remoteEvents = $remoteEvents instanceof RemoteEvent ? [$remoteEvents] : $remoteEvents;

        foreach ($remoteEvents as $event) {
            if (!$event instanceof RemoteEvent || '' === $event->getId()) {
                return new Response('', Response::HTTP_BAD_REQUEST);
            }
        }

        foreach ($remoteEvents as $remoteEvent) {
            $endpointId = (int) $endpoint['id'];
            $eventId = $this->repository->storeIncoming($endpointId, $remoteEvent->getId());

            if (null !== $eventId) {
                try {
                    $this->messageBus->dispatch(new ConsumeWebhookMessage($eventId, $endpointId, $endpoint['receiver'], $remoteEvent->getName(), $remoteEvent->getId(), $remoteEvent->getPayload()));
                } catch (\Throwable $exception) {
                    $this->repository->deleteIncoming($eventId);

                    throw $exception;
                }
            }
        }

        return $parser->createSuccessfulResponse($request);
    }
}
