<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\WebhooksBundle\DependencyInjection;

use Contao\WebhooksBundle\Webhook\Attribute\AsWebhookEvent;
use Contao\WebhooksBundle\Webhook\Attribute\AsWebhookReceiver;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ChildDefinition;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Extension\Extension;
use Symfony\Component\DependencyInjection\Extension\PrependExtensionInterface;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;

class ContaoWebhooksExtension extends Extension implements PrependExtensionInterface
{
    public function getAlias(): string
    {
        return 'webhooks';
    }

    public function load(array $configs, ContainerBuilder $container): void
    {
        $config = $this->processConfiguration(new Configuration(), $configs);

        $container->setParameter('contao.webhook.request_body_limit', $config['request_body_limit']);
        $container->setParameter('contao.webhook.rate_limit', $config['rate_limit']);
        $container->setParameter('contao.webhook.connect_timeout', $config['connect_timeout']);
        $container->setParameter('contao.webhook.total_timeout', $config['total_timeout']);
        $container->setParameter('contao.webhook.max_redirects', $config['max_redirects']);

        $container->registerAttributeForAutoconfiguration(
            AsWebhookReceiver::class,
            static function (ChildDefinition $definition, AsWebhookReceiver $attribute): void {
                $definition->addTag('contao.webhook_receiver', ['name' => $attribute->name, 'parser' => $attribute->parser]);
                $definition->addTag('remote_event.consumer', ['consumer' => $attribute->name]);
            },
        );

        $container->registerAttributeForAutoconfiguration(
            AsWebhookEvent::class,
            static function (ChildDefinition $definition, AsWebhookEvent $attribute): void {
                $definition->addTag('contao.webhook_event', ['name' => $attribute->name]);
            },
        );

        $loader = new YamlFileLoader($container, new FileLocator(__DIR__.'/../../config'));
        $loader->load('services.yaml');

        if ($config['allow_private_networks']) {
            $container->removeDefinition('contao.http_client.webhook');
            $container->setAlias('contao.http_client.webhook', 'http_client');
        }
    }

    public function prepend(ContainerBuilder $container): void
    {
        $container->prependExtensionConfig('framework', [
            'messenger' => [
                'routing' => [
                    'Contao\WebhooksBundle\Webhook\Message\ConsumeWebhookMessage' => 'contao_prio_high',
                    'Contao\WebhooksBundle\Webhook\Message\DeliverWebhookMessage' => 'contao_prio_high',
                ],
            ],
        ]);

        $container->prependExtensionConfig('monolog', [
            'channels' => ['contao.webhook'],
        ]);

        $container->prependExtensionConfig('contao', [
            'backend' => [
                'custom_css' => ['bundles/contaowebhooks/css/backend.css'],
            ],
        ]);
    }
}
