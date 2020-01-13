<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Tests\DependencyInjection\Security;

use Contao\CoreBundle\DependencyInjection\Security\ContaoLoginFactory;
use Contao\CoreBundle\Tests\TestCase;
use Symfony\Component\DependencyInjection\ContainerBuilder;

class ContaoLoginFactoryTest extends TestCase
{
    public function testReturnsTheCorrectKey(): void
    {
        $this->assertSame('contao-login', (new ContaoLoginFactory())->getKey());
    }

    public function testConfiguresTheContainerServices(): void
    {
        $container = new ContainerBuilder();
        $factory = new ContaoLoginFactory();

        [$authProviderId, $listenerId, $defaultEntryPoint] = $factory->create(
            $container,
            'contao_frontend',
            ['remember_me' => true],
            'contao.security.frontend_user_provider',
            null
        );

        $this->assertSame('contao.security.authentication_provider.contao_frontend', $authProviderId);
        $this->assertSame('contao.security.authentication_listener.contao_frontend', $listenerId);
        $this->assertSame('contao.security.entry_point', $defaultEntryPoint);
        $this->assertTrue($container->hasDefinition('contao.security.authentication_provider.contao_frontend'));

        $arguments = $container
            ->getDefinition('contao.security.authentication_provider.contao_frontend')
            ->getArguments()
        ;

        $this->assertIsArray($arguments);
        $this->assertCount(3, $arguments);
        $this->assertSame('contao.security.frontend_user_provider', (string) $arguments['index_0']);
        $this->assertSame('security.user_checker.contao_frontend', (string) $arguments['index_1']);
        $this->assertSame('contao_frontend', $arguments['index_2']);
    }
}
