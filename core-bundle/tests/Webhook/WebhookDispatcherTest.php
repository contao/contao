<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Tests\Webhook;

use Contao\CoreBundle\Tests\TestCase;
use Contao\CoreBundle\Webhook\Message\DeliverWebhookMessage;
use Contao\CoreBundle\Webhook\WebhookDispatcher;
use Contao\CoreBundle\Webhook\WebhookEventInterface;
use Contao\CoreBundle\Webhook\WebhookEventRegistry;
use Contao\CoreBundle\Webhook\WebhookRepository;
use Doctrine\DBAL\Connection;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\MessageBusInterface;

final class WebhookDispatcherTest extends TestCase
{
    public function testDispatchesToMatchingSubscriptions(): void
    {
        $event = new DispatcherTestEvent('external-id');
        $connection = $this->createMock(Connection::class);
        $connection
            ->expects($this->once())
            ->method('fetchAllAssociative')
            ->willReturn([
                ['id' => '3', 'events' => ['example.event']],
                ['id' => '8', 'events' => ['example.event']],
            ])
        ;
        $repository = new WebhookRepository($connection, $this->createStub(EntityManagerInterface::class));

        $messageBus = $this->createMock(MessageBusInterface::class);
        $messageBus
            ->expects($this->exactly(2))
            ->method('dispatch')
            ->with($this->callback(static fn (DeliverWebhookMessage $message): bool => \in_array($message->subscriptionId, [3, 8], true) && 'example.event' === $message->name && 'external-id' === $message->externalId && ['value' => 42] === $message->payload))
            ->willReturn(new Envelope(new \stdClass()))
        ;

        $dispatcher = new WebhookDispatcher(new WebhookEventRegistry([DispatcherTestEvent::class => ['name' => 'example.event', 'properties' => []]]), $repository, $messageBus);
        $dispatcher->dispatch($event);
    }

    public function testRejectsUnregisteredEvent(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('is not registered');

        new WebhookDispatcher(new WebhookEventRegistry(), $this->repository(), $this->createStub(MessageBusInterface::class))->dispatch(new DispatcherTestEvent('id'));
    }

    public function testRejectsNonJsonPayload(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('JSON-compatible');

        $event = new DispatcherTestEvent('id');
        $dispatcher = new WebhookDispatcher(new WebhookEventRegistry([DispatcherTestEvent::class => ['name' => 'example.event', 'properties' => []]]), $this->repository(), $this->createStub(MessageBusInterface::class));
        $dispatcher->dispatch($event->withInvalidPayload());
    }

    private function repository(): WebhookRepository
    {
        return new WebhookRepository($this->createStub(Connection::class), $this->createStub(EntityManagerInterface::class));
    }
}

final readonly class DispatcherTestEvent implements WebhookEventInterface
{
    public function __construct(
        private string $id,
        private bool $invalid = false,
    ) {
    }

    public function getId(): string
    {
        return $this->id;
    }

    public function getPayload(): array
    {
        return $this->invalid ? ['resource' => fopen('php://memory', 'r')] : ['value' => 42];
    }

    public function withInvalidPayload(): self
    {
        return new self($this->id, true);
    }
}
