<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * Copyright (c) 2005-2018 Leo Feyer
 *
 * @license LGPL-3.0+
 */

namespace Contao\CoreBundle\Tests\DependencyInjection\Compiler;

use Contao\CoreBundle\DependencyInjection\Compiler\MakeServicesPublicPass;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;

class MakeServicesPublicPassTest extends TestCase
{
    public function testCanBeInstantiated(): void
    {
        $pass = new MakeServicesPublicPass();

        $this->assertInstanceOf('Contao\CoreBundle\DependencyInjection\Compiler\MakeServicesPublicPass', $pass);
    }

    public function testMakesTheServicesPublic(): void
    {
        $container = new ContainerBuilder();
        $container->setDefinition('assets.packages', (new Definition())->setPublic(false));
        $container->setDefinition('lexik_maintenance.driver.factory', (new Definition())->setPublic(false));
        $container->setDefinition('monolog.logger.contao', (new Definition())->setPublic(false));
        $container->setDefinition('security.authentication.trust_resolver', (new Definition())->setPublic(false));
        $container->setDefinition('security.firewall.map', (new Definition())->setPublic(false));
        $container->setDefinition('security.logout_url_generator', (new Definition())->setPublic(false));
        $container->setDefinition('swiftmailer.mailer', (new Definition())->setPublic(false));

        $pass = new MakeServicesPublicPass();
        $pass->process($container);

        $this->assertTrue($container->getDefinition('assets.packages')->isPublic());
        $this->assertTrue($container->getDefinition('lexik_maintenance.driver.factory')->isPublic());
        $this->assertTrue($container->getDefinition('monolog.logger.contao')->isPublic());
        $this->assertTrue($container->getDefinition('security.authentication.trust_resolver')->isPublic());
        $this->assertTrue($container->getDefinition('security.firewall.map')->isPublic());
        $this->assertTrue($container->getDefinition('security.logout_url_generator')->isPublic());
        $this->assertTrue($container->getDefinition('swiftmailer.mailer')->isPublic());
    }

    public function testMakesTheAliasedServicesPublic(): void
    {
        $container = new ContainerBuilder();
        $container->setDefinition('doctrine.dbal.default_connection', (new Definition())->setPublic(false));
        $container->setAlias('database_connection', 'doctrine.dbal.default_connection');
        $container->setDefinition('contao.fragment.handler', (new Definition())->setPublic(false));
        $container->setAlias('fragment.handler', 'contao.fragment.handler');

        $pass = new MakeServicesPublicPass();
        $pass->process($container);

        $this->assertTrue($container->getDefinition('doctrine.dbal.default_connection')->isPublic());
        $this->assertTrue($container->getDefinition('contao.fragment.handler')->isPublic());
    }
}
