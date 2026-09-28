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

use Contao\CoreBundle\DependencyInjection\Compiler\WebhookRegistryPass;
use Contao\CoreBundle\Tests\TestCase;
use Contao\CoreBundle\Webhook\Attribute\AsWebhookEvent;
use Contao\CoreBundle\Webhook\Attribute\AsWebhookEventProvider;
use Contao\CoreBundle\Webhook\Attribute\AsWebhookReceiver;
use Contao\CoreBundle\Webhook\IncomingWebhookContext;
use Contao\CoreBundle\Webhook\WebhookConsumerInterface;
use Contao\CoreBundle\Webhook\WebhookEventInterface;
use Contao\CoreBundle\Webhook\WebhookEventRegistry;
use Contao\CoreBundle\Webhook\WebhookReceiverRegistry;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Exception\InvalidArgumentException;
use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\RemoteEvent\RemoteEvent;
use Symfony\Component\Webhook\Client\RequestParserInterface;

class WebhookRegistryTest extends TestCase
{
    public function testBuildsEventAndReceiverRegistries(): void
    {
        $container = new ContainerBuilder();
        $container->setDefinition('contao.webhook.receiver_registry', new Definition(WebhookReceiverRegistry::class, [[], new Reference('service_container')]));
        $container->setDefinition('contao.webhook.event_registry', new Definition(WebhookEventRegistry::class, [[]]));
        $container->setDefinition(TestWebhookReceiver::class, new Definition(TestWebhookReceiver::class)->addTag('contao.webhook_receiver', ['name' => 'vendor.example', 'parser' => TestWebhookParser::class]));
        $container->setDefinition(TestWebhookParser::class, new Definition(TestWebhookParser::class));
        $container->setDefinition(TestWebhookProvider::class, new Definition(TestWebhookProvider::class)->addTag('contao.webhook_event_provider'));

        $pass = new WebhookRegistryPass();
        $pass->process($container);

        $this->assertSame(['consumer' => TestWebhookReceiver::class, 'parser' => TestWebhookParser::class], $container->getDefinition('contao.webhook.receiver_registry')->getArgument(0)['vendor.example']);
        $eventMetadata = $container->getDefinition('contao.webhook.event_registry')->getArgument(0)[TestWebhookEvent::class];
        $this->assertSame('test.event', $eventMetadata['name']);
        $this->assertSame('id', $eventMetadata['properties'][0]['name']);
    }

    public function testRejectsDuplicateReceiverNames(): void
    {
        $container = new ContainerBuilder();
        $container->setDefinition('contao.webhook.receiver_registry', new Definition(WebhookReceiverRegistry::class, [[], new Reference('service_container')]));
        $container->setDefinition('contao.webhook.event_registry', new Definition(WebhookEventRegistry::class, [[]]));
        $container->setDefinition('receiver.one', new Definition(TestWebhookReceiver::class)->addTag('contao.webhook_receiver', ['name' => 'vendor.example', 'parser' => TestWebhookParser::class]));
        $container->setDefinition('receiver.two', new Definition(TestWebhookReceiver::class)->addTag('contao.webhook_receiver', ['name' => 'vendor.example', 'parser' => TestWebhookParser::class]));
        $container->setDefinition(TestWebhookParser::class, new Definition(TestWebhookParser::class));

        $this->expectException(InvalidArgumentException::class);

        new WebhookRegistryPass()->process($container);
    }

    public function testRejectsDuplicateEventNames(): void
    {
        $container = new ContainerBuilder();
        $container->setDefinition('contao.webhook.receiver_registry', new Definition(WebhookReceiverRegistry::class, [[], new Reference('service_container')]));
        $container->setDefinition('contao.webhook.event_registry', new Definition(WebhookEventRegistry::class, [[]]));
        $container->setDefinition(TestWebhookProvider::class, new Definition(TestWebhookProvider::class)->addTag('contao.webhook_event_provider'));
        $container->setDefinition(TestDuplicateWebhookProvider::class, new Definition(TestDuplicateWebhookProvider::class)->addTag('contao.webhook_event_provider'));

        $this->expectException(InvalidArgumentException::class);

        new WebhookRegistryPass()->process($container);
    }
}

#[AsWebhookReceiver('vendor.example', TestWebhookParser::class)]
final class TestWebhookReceiver implements WebhookConsumerInterface
{
    public function consume(RemoteEvent $event, IncomingWebhookContext $context): void
    {
    }
}

final class TestWebhookParser implements RequestParserInterface
{
    public function parse(Request $request, #[\SensitiveParameter] string $secret): RemoteEvent|array|null
    {
        return null;
    }

    public function createSuccessfulResponse(Request|null $request = null): Response
    {
        return new Response();
    }

    public function createRejectedResponse(string $reason, Request|null $request = null): Response
    {
        return new Response($reason, 400);
    }
}

#[AsWebhookEvent('test.event')]
final readonly class TestWebhookEvent implements WebhookEventInterface
{
    public function __construct(public string $id)
    {
    }

    public function getId(): string
    {
        return $this->id;
    }

    public function getPayload(): array
    {
        return ['id' => $this->id];
    }
}

#[AsWebhookEventProvider]
final class TestWebhookProvider
{
    public static function getEvents(): iterable
    {
        yield TestWebhookEvent::class;
    }
}

#[AsWebhookEventProvider]
final class TestDuplicateWebhookProvider
{
    public static function getEvents(): iterable
    {
        yield TestWebhookEvent::class;
    }
}
