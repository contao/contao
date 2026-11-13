<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\WebhooksBundle;

use Contao\WebhooksBundle\DependencyInjection\Compiler\WebhookRegistryPass;
use Contao\WebhooksBundle\DependencyInjection\ContaoWebhooksExtension;
use Symfony\Component\DependencyInjection\Compiler\PassConfig;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Extension\ExtensionInterface;
use Symfony\Component\HttpKernel\Bundle\Bundle;

class ContaoWebhooksBundle extends Bundle
{
    private ExtensionInterface|null $containerExtension = null;

    #[\Override]
    public function getContainerExtension(): ExtensionInterface|null
    {
        return $this->containerExtension ??= new ContaoWebhooksExtension();
    }

    #[\Override]
    public function getPath(): string
    {
        return \dirname(__DIR__);
    }

    public function build(ContainerBuilder $container): void
    {
        parent::build($container);

        $container->addCompilerPass(new WebhookRegistryPass(), PassConfig::TYPE_BEFORE_OPTIMIZATION, -10);
    }
}
