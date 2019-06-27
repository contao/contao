<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Tests\DependencyInjection\Compiler;

use Contao\CoreBundle\DependencyInjection\Compiler\MakeServicesPublicPass;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;

class MakeServicesPublicPassTest extends TestCase
{
    public function testMakesTheServicesPublic(): void
    {
        $container = new ContainerBuilder();

        // Definitions
        $container->setDefinition('assets.packages', (new Definition())->setPublic(false));
        $container->setDefinition('fragment.handler', (new Definition())->setPublic(false));
        $container->setDefinition('lexik_maintenance.driver.factory', (new Definition())->setPublic(false));
        $container->setDefinition('monolog.logger.contao', (new Definition())->setPublic(false));
        $container->setDefinition('security.authentication.trust_resolver', (new Definition())->setPublic(false));
        $container->setDefinition('security.firewall.map', (new Definition())->setPublic(false));
        $container->setDefinition('security.logout_url_generator', (new Definition())->setPublic(false));

        // Aliased definitions
        $container->setDefinition('doctrine.dbal.default_connection', (new Definition())->setPublic(false));
        $container->setDefinition('swiftmailer.mailer.default', (new Definition())->setPublic(false));

        // Aliases
        $container->setAlias('database_connection', 'doctrine.dbal.default_connection');
        $container->setAlias('swiftmailer.mailer', 'swiftmailer.mailer.default');
        $container->setAlias('security.encoder_factory', 'security.encoder_factory.generic');

        $pass = new MakeServicesPublicPass();
        $pass->process($container);

        // Definitions
        $this->assertTrue($container->getDefinition('assets.packages')->isPublic());
        $this->assertTrue($container->getDefinition('fragment.handler')->isPublic());
        $this->assertTrue($container->getDefinition('lexik_maintenance.driver.factory')->isPublic());
        $this->assertTrue($container->getDefinition('monolog.logger.contao')->isPublic());
        $this->assertTrue($container->getDefinition('security.authentication.trust_resolver')->isPublic());
        $this->assertTrue($container->getDefinition('security.firewall.map')->isPublic());
        $this->assertTrue($container->getDefinition('security.logout_url_generator')->isPublic());

        // Aliases
        $this->assertTrue($container->getAlias('database_connection')->isPublic());
        $this->assertTrue($container->getAlias('swiftmailer.mailer')->isPublic());
        $this->assertTrue($container->getAlias('security.encoder_factory')->isPublic());
    }
}
