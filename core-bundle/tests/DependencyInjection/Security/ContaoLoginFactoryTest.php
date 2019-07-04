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
        $factory = new ContaoLoginFactory();

        $this->assertSame('contao-login', $factory->getKey());
    }

    public function testConfiguresTheContainerServices(): void
    {
        $config = [
            'login_path' => 'contao_frontend_login',
            'lock_period' => 300,
            'login_attempts' => 3,
            'remember_me' => true,
            'use_forward' => false,
        ];

        $container = new ContainerBuilder();

        $factory = new ContaoLoginFactory();
        $factory->create($container, 'contao_frontend', $config, 'contao.security.frontend_user_provider', null);

        $this->assertTrue($container->hasDefinition('contao.security.authentication_provider.contao_frontend'));

        $arguments = $container
            ->getDefinition('contao.security.authentication_provider.contao_frontend')
            ->getArguments()
        ;

        $this->assertIsArray($arguments);
        $this->assertCount(4, $arguments);
        $this->assertSame('contao.security.frontend_user_provider', (string) $arguments['index_0']);
        $this->assertSame('security.user_checker.contao_frontend', (string) $arguments['index_1']);
        $this->assertSame('contao_frontend', $arguments['index_2']);

        $this->assertSame(
            [
                'lock_period' => 300,
                'login_attempts' => 3,
            ],
            $arguments[0]
        );

        $this->assertTrue($container->hasDefinition('security.authentication.listener.form.contao_frontend'));

        $arguments = $container
            ->getDefinition('security.authentication.listener.form.contao_frontend')
            ->getArguments()
        ;

        $this->assertIsArray($arguments);
        $this->assertCount(5, $arguments);

        $this->assertSame('contao_frontend', $arguments['index_4']);

        $this->assertSame(
            'security.authentication.success_handler.contao_frontend.contao_login',
            (string) $arguments['index_5']
        );

        $this->assertSame(
            'security.authentication.failure_handler.contao_frontend.contao_login',
            (string) $arguments['index_6']
        );

        $this->assertSame(
            [
                'lock_period' => 300,
                'login_attempts' => 3,
                'use_forward' => false,
                'username_parameter' => 'username',
                'password_parameter' => 'password',
            ],
            $arguments['index_7']
        );
    }
}
