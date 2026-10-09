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

use Contao\CoreBundle\Entity\WebhookEvent;
use Contao\CoreBundle\Tests\TestCase;
use Contao\CoreBundle\Webhook\Message\ConsumeWebhookMessage;
use Contao\CoreBundle\Webhook\MessageHandler\ConsumeWebhookHandler;
use Contao\CoreBundle\Webhook\WebhookRepository;
use Doctrine\DBAL\Connection;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;
use Symfony\Component\DependencyInjection\ServiceLocator;
use Symfony\Component\RemoteEvent\Consumer\ConsumerInterface;
use Symfony\Component\RemoteEvent\Messenger\ConsumeRemoteEventHandler;
use Symfony\Component\RemoteEvent\RemoteEvent;

final class ConsumeWebhookHandlerTest extends TestCase
{
    public function testConsumesRemoteEventBeforeMarkingItProcessed(): void
    {
        $event = new WebhookEvent(3, 'external-id');
        $consumer = $this->createMock(ConsumerInterface::class);
        $consumer
            ->expects($this->once())
            ->method('consume')
            ->with($this->callback(static fn (RemoteEvent $remoteEvent): bool => 'processing' === $event->getProcessingState() && 'example.event' === $remoteEvent->getName() && 'external-id' === $remoteEvent->getId() && ['value' => 42] === $remoteEvent->getPayload()))
        ;

        ($this->createHandler($event, $consumer))($this->message());

        $this->assertSame('processed', $event->getProcessingState());
    }

    public function testReleasesClaimWhenConsumerFails(): void
    {
        $event = new WebhookEvent(3, 'external-id');
        $exception = new \RuntimeException('Consumer failed.');
        $consumer = $this->createMock(ConsumerInterface::class);
        $consumer
            ->expects($this->once())
            ->method('consume')
            ->willThrowException($exception)
        ;

        try {
            ($this->createHandler($event, $consumer))($this->message());
            $this->fail('The consumer exception must be rethrown.');
        } catch (\RuntimeException $caught) {
            $this->assertSame($exception, $caught);
        }

        $this->assertSame('pending', $event->getProcessingState());
    }

    public function testDoesNotConsumeProcessedEvent(): void
    {
        $event = new WebhookEvent(3, 'external-id');
        $event->markProcessed();

        $consumer = $this->createMock(ConsumerInterface::class);
        $consumer
            ->expects($this->never())
            ->method('consume')
        ;

        ($this->createHandler($event, $consumer))($this->message());

        $this->assertSame('processed', $event->getProcessingState());
    }

    public function testServiceConfigurationUsesSymfonyConsumerLocator(): void
    {
        $definitions = new ContainerBuilder();
        new YamlFileLoader($definitions, new FileLocator(__DIR__.'/../../config'))->load('services.yaml');

        $event = new WebhookEvent(3, 'external-id');
        $consumer = $this->createMock(ConsumerInterface::class);
        $consumer
            ->expects($this->once())
            ->method('consume')
        ;

        $container = new ContainerBuilder();
        $handler = $definitions->getDefinition('contao.webhook.message_handler.consume_handler')->setPublic(true);
        $consumerHandlerId = (string) $handler->getArgument(1);
        $container->setDefinition('handler', $handler);
        $container->setDefinition($consumerHandlerId, $definitions->getDefinition($consumerHandlerId));
        $container->setDefinition('contao.webhook.repository', new Definition(WebhookRepository::class)->setSynthetic(true));
        $container->setDefinition('consumer', new Definition(ConsumerInterface::class)->setSynthetic(true)->addTag('remote_event.consumer', ['consumer' => 'vendor.example']));
        $container->compile();
        $container->set('contao.webhook.repository', $this->createRepository($event));
        $container->set('consumer', $consumer);

        ($container->get('handler'))($this->message());

        $this->assertSame('processed', $event->getProcessingState());
    }

    private function createHandler(WebhookEvent $event, ConsumerInterface $consumer): ConsumeWebhookHandler
    {
        return new ConsumeWebhookHandler(
            $this->createRepository($event),
            new ConsumeRemoteEventHandler(new ServiceLocator(['vendor.example' => static fn (): ConsumerInterface => $consumer])),
        );
    }

    private function createRepository(WebhookEvent $event): WebhookRepository
    {
        $entityRepository = $this->createStub(EntityRepository::class);
        $entityRepository
            ->method('findOneBy')
            ->willReturn($event)
        ;
        $entityManager = $this->createStub(EntityManagerInterface::class);
        $entityManager
            ->method('getRepository')
            ->willReturn($entityRepository)
        ;

        $entityManager
            ->method('wrapInTransaction')
            ->willReturnCallback(static fn (callable $callback): mixed => $callback())
        ;

        return new WebhookRepository($this->createStub(Connection::class), $entityManager);
    }

    private function message(): ConsumeWebhookMessage
    {
        return new ConsumeWebhookMessage(7, 3, 'vendor.example', 'example.event', 'external-id', ['value' => 42]);
    }
}
