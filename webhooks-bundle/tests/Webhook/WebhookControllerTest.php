<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\WebhooksBundle\Tests\Webhook;

use Contao\TestCase\ContaoTestCase;
use Contao\WebhooksBundle\Controller\WebhookController;
use Contao\WebhooksBundle\Webhook\SecretEncryption;
use Contao\WebhooksBundle\Webhook\WebhookReceiverRegistry;
use Contao\WebhooksBundle\Webhook\WebhookRepository;
use Doctrine\DBAL\Connection;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Container\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\RateLimiter\RateLimiterFactory;
use Symfony\Component\RateLimiter\Storage\InMemoryStorage;

final class WebhookControllerTest extends ContaoTestCase
{
    public function testRejectsUnknownEndpoint(): void
    {
        $connection = $this->createMock(Connection::class);
        $connection
            ->expects($this->once())
            ->method('fetchAssociative')
            ->willReturn(false)
        ;
        $repository = new WebhookRepository($connection, $this->createStub(EntityManagerInterface::class));

        $receivers = new WebhookReceiverRegistry([], $this->createStub(ContainerInterface::class));
        $messageBus = $this->createStub(MessageBusInterface::class);

        $response = ($this->createController($repository, $receivers, $messageBus))($this->request(), $this->token());

        $this->assertSame(Response::HTTP_NOT_FOUND, $response->getStatusCode());
    }

    private function createController(WebhookRepository $repository, WebhookReceiverRegistry $receivers, MessageBusInterface $messageBus): WebhookController
    {
        return new WebhookController(
            $repository,
            $receivers,
            new SecretEncryption('test'),
            $messageBus,
            new RateLimiterFactory(['id' => 'webhook', 'policy' => 'fixed_window', 'limit' => 10, 'interval' => '1 minute'], new InMemoryStorage()),
        );
    }

    private function request(): Request
    {
        return Request::create('/', 'POST', [], [], [], ['CONTENT_LENGTH' => '2'], '{}');
    }

    private function token(): string
    {
        return '01234567890123456789012345678901';
    }
}
