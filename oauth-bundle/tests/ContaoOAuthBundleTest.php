<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\OAuthBundle\Tests;

use Contao\OAuthBundle\ContaoOAuthBundle;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Config\Definition\Exception\InvalidConfigurationException;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBag;

final class ContaoOAuthBundleTest extends TestCase
{
    public function testUsesTheContaoOAuthAlias(): void
    {
        $this->assertSame('contao_oauth', new ContaoOAuthBundle()->getContainerExtension()->getAlias());
    }

    public function testSetsTheResourceParameters(): void
    {
        $container = new ContainerBuilder(new ParameterBag(['kernel.environment' => 'test', 'kernel.build_dir' => sys_get_temp_dir()]));

        $extension = new ContaoOAuthBundle()->getContainerExtension();
        $extension->load(
            [
                ['resource' => ['route' => 'contao_mcp_backend', 'scopes' => ['mcp']], 'cimd_trusted_domains' => ['claude.ai']],
            ],
            $container,
        );

        $this->assertSame('contao_mcp_backend', $container->getParameter('contao_oauth.resource.route'));
        $this->assertSame(['mcp'], $container->getParameter('contao_oauth.resource.scopes'));
        $this->assertSame('Contao', $container->getParameter('contao_oauth.resource.name'));
        $this->assertSame(['claude.ai'], $container->getParameter('contao_oauth.cimd_trusted_domains'));
        $this->assertTrue($container->hasDefinition('contao_oauth.security.bearer_authenticator'));
    }

    public function testRequiresAResource(): void
    {
        $extension = new ContaoOAuthBundle()->getContainerExtension();

        $this->expectException(InvalidConfigurationException::class);

        $extension->load([[]], new ContainerBuilder(new ParameterBag(['kernel.environment' => 'test', 'kernel.build_dir' => sys_get_temp_dir()])));
    }
}
