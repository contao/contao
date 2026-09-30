<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\OAuthServerBundle\Tests;

use Contao\OAuthServerBundle\ContaoOAuthServerBundle;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Config\Definition\Exception\InvalidConfigurationException;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBag;

final class ContaoOAuthBundleTest extends TestCase
{
    public function testUsesTheContaoOAuthAlias(): void
    {
        $this->assertSame('contao_oauth_server', new ContaoOAuthServerBundle()->getContainerExtension()->getAlias());
    }

    public function testSetsTheResourceParameters(): void
    {
        $container = new ContainerBuilder(new ParameterBag(['kernel.environment' => 'test', 'kernel.build_dir' => sys_get_temp_dir()]));

        $extension = new ContaoOAuthServerBundle()->getContainerExtension();
        $extension->load(
            [
                ['resource' => ['route' => 'contao_mcp_backend', 'scopes' => ['mcp']], 'cimd_trusted_domains' => ['claude.ai']],
            ],
            $container,
        );

        $this->assertSame('contao_mcp_backend', $container->getParameter('contao_oauth_server.resource.route'));
        $this->assertSame(['mcp'], $container->getParameter('contao_oauth_server.resource.scopes'));
        $this->assertSame('Contao', $container->getParameter('contao_oauth_server.resource.name'));
        $this->assertSame(['claude.ai'], $container->getParameter('contao_oauth_server.cimd_trusted_domains'));
        $this->assertTrue($container->hasDefinition('contao_oauth_server.security.bearer_authenticator'));
    }

    public function testRequiresAResource(): void
    {
        $extension = new ContaoOAuthServerBundle()->getContainerExtension();

        $this->expectException(InvalidConfigurationException::class);

        $extension->load([[]], new ContainerBuilder(new ParameterBag(['kernel.environment' => 'test', 'kernel.build_dir' => sys_get_temp_dir()])));
    }
}
