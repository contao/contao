<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\WebhooksBundle\Tests\DependencyInjection;

use Contao\TestCase\ContaoTestCase;
use Contao\WebhooksBundle\ContaoWebhooksBundle;
use Contao\WebhooksBundle\Controller\WebhookController;
use Contao\WebhooksBundle\DependencyInjection\ContaoWebhooksExtension;
use Contao\WebhooksBundle\Webhook\Attribute\AsWebhookEvent;
use Contao\WebhooksBundle\Webhook\Attribute\AsWebhookReceiver;
use Symfony\Component\DependencyInjection\ChildDefinition;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Extension\PrependExtensionInterface;

final class ContaoWebhooksExtensionTest extends ContaoTestCase
{
    public function testBundleUsesWebhookConfigurationAlias(): void
    {
        $extension = new ContaoWebhooksBundle()->getContainerExtension();

        $this->assertInstanceOf(ContaoWebhooksExtension::class, $extension);
        $this->assertSame('webhooks', $extension->getAlias());
    }

    public function testLoadsWebhookServicesAndConfiguration(): void
    {
        $container = new ContainerBuilder();

        new ContaoWebhooksExtension()->load([['rate_limit' => 24]], $container);

        $this->assertSame(1048576, $container->getParameter('contao.webhook.request_body_limit'));
        $this->assertSame(24, $container->getParameter('contao.webhook.rate_limit'));
        $this->assertTrue($container->hasDefinition('contao.webhook.dispatcher'));
        $this->assertTrue($container->hasDefinition(WebhookController::class));
    }

    public function testPrependsWebhookMessengerRoutingAndLoggerChannel(): void
    {
        $container = new ContainerBuilder();
        $extension = new ContaoWebhooksExtension();

        $this->assertInstanceOf(PrependExtensionInterface::class, $extension);

        $extension->prepend($container);

        $this->assertSame(
            [
                'messenger' => [
                    'routing' => [
                        'Contao\\WebhooksBundle\\Webhook\\Message\\ConsumeWebhookMessage' => 'contao_prio_high',
                        'Contao\\WebhooksBundle\\Webhook\\Message\\DeliverWebhookMessage' => 'contao_prio_high',
                    ],
                ],
            ],
            $container->getExtensionConfig('framework')[0],
        );
        $this->assertSame(['channels' => ['contao.webhook']], $container->getExtensionConfig('monolog')[0]);
        $this->assertSame(
            ['backend' => ['custom_css' => ['bundles/contaowebhooks/css/backend.css']]],
            $container->getExtensionConfig('contao')[0],
        );
    }

    public function testRegistersWebhookAttributesForAutoconfiguration(): void
    {
        $container = new ContainerBuilder();

        new ContaoWebhooksExtension()->load([], $container);

        $configurators = $container->getAttributeAutoconfigurators();
        $this->assertArrayHasKey(AsWebhookReceiver::class, $configurators);
        $this->assertArrayHasKey(AsWebhookEvent::class, $configurators);

        $definition = new ChildDefinition('receiver');
        $configurators[AsWebhookReceiver::class][0]($definition, new AsWebhookReceiver('vendor.example', 'ExampleParser'));

        $this->assertSame([['name' => 'vendor.example', 'parser' => 'ExampleParser']], $definition->getTag('contao.webhook_receiver'));
        $this->assertSame([['consumer' => 'vendor.example']], $definition->getTag('remote_event.consumer'));
    }
}
