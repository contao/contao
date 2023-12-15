<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Tests\Functional\Dca;

use Contao\Controller;
use Contao\CoreBundle\Dca\DcaFactory;
use Contao\CoreBundle\Dca\Driver\DriverCollection;
use Contao\CoreBundle\Dca\Driver\GlobalArrayDriver;
use Contao\CoreBundle\Dca\Provider\ConfigurationProvider;
use Contao\CoreBundle\Dca\SchemaFactory;
use Contao\CoreBundle\Tests\TestCase;
use Contao\System;
use Psr\Container\ContainerInterface;
use Symfony\Component\EventDispatcher\EventDispatcher;

class DcaTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        require $this->getFixturesDir().'/Functional/Dca/tl_test.php';
    }

    protected function tearDown(): void
    {
        parent::tearDown();

        unset($GLOBALS['TL_DCA']);
    }

    public function testReadsFromGlobals(): void
    {
        $dca = $this->getDcaFactory()->get('tl_test');

        $this->assertTrue($dca->config()->usesVersioning());
        $this->assertTrue($dca->get('config.enableVersioning'));
    }

    public function testDetectsChangesInGlobals(): void
    {
        $dca = $this->getDcaFactory()->get('tl_test');

        // Detach a schema from the DCA to validate its data gets updates as well
        $config = $dca->config();

        $this->assertTrue($config->usesVersioning());
        $this->assertTrue($dca->config()->usesVersioning());
        $this->assertTrue($dca->get('config.enableVersioning'));

        $GLOBALS['TL_DCA']['tl_test']['config']['enableVersioning'] = false;

        $this->assertFalse($config->usesVersioning());
        $this->assertFalse($dca->get('config.enableVersioning'));
        $this->assertFalse($dca->config()->usesVersioning());
    }

    public function testCallbackNodes(): void
    {
        $dca = $this->getDcaFactory()->get('tl_test');

        $dca->config()->callback('onload')->call();
    }

    protected function getDcaFactory(): DcaFactory
    {
        $container = $this->getContainerWithContaoConfiguration();

        // TODO: Make this mockable / spyable!
        $callbackConsumer = new class() {
            public function __call(string $method, array $arguments): void
            {
            }
        };

        $controllerAdapter = $this->mockAdapter(['loadDataContainer']);
        $systemAdapter = $this->mockConfiguredAdapter([
            'importStatic' => $callbackConsumer,
        ]);

        $framework = $this->mockContaoFramework([
            Controller::class => $controllerAdapter,
            System::class => $systemAdapter,
        ]);

        $controllerAdapter
            // Allow some tests to load the data container more than once, e.g. to reload after changes
            ->expects($this->atLeastOnce())
            ->method('loadDataContainer')
            ->with('tl_test')
        ;

        $locator = $this->createMock(ContainerInterface::class);
        $locator
            ->method('get')
            ->willReturnMap([
                ['service_container', $container],
                ['contao.framework', $framework],
            ])
        ;

        $factory = new SchemaFactory($locator);
        $drivers = new DriverCollection([new GlobalArrayDriver($framework)]);
        $configuration = new ConfigurationProvider($drivers, new EventDispatcher());

        return new DcaFactory($factory, $drivers, $configuration);
    }
}
