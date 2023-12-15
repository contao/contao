<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Dca;

use Contao\CoreBundle\Dca\Data;
use Contao\CoreBundle\Dca\DcaFactory;
use Contao\CoreBundle\Dca\Driver\DriverCollection;
use Contao\CoreBundle\Dca\Driver\DriverInterface;
use Contao\CoreBundle\Dca\Provider\ConfigurationProviderInterface;
use Contao\CoreBundle\Dca\Schema\Dca;
use Contao\CoreBundle\Dca\SchemaFactory;
use Contao\CoreBundle\Tests\TestCase;

class DcaFactoryTest extends TestCase
{
    public function testCreatesDcaObjectViaSchemaFactory(): void
    {
        $data = ['config' => []];

        $schemaFactory = $this->createMock(SchemaFactory::class);
        $driverCollection = $this->createMock(DriverCollection::class);
        $configuration = $this->createMock(ConfigurationProviderInterface::class);
        $driver = $this->createMock(DriverInterface::class);
        $dca = $this->createMock(Dca::class);

        $configuration
            ->expects($this->once())
            ->method('getConfiguration')
            ->with('tl_foo', $driver)
            ->willReturn($data)
        ;

        $driverCollection
            ->expects($this->once())
            ->method('getDriverForResource')
            ->with('tl_foo')
            ->willReturn($driver)
        ;

        $schemaFactory
            ->expects($this->once())
            ->method('createSchema')
            ->with('tl_foo', Dca::class, new Data($data))
            ->willReturn($dca)
        ;

        $factory = new DcaFactory($schemaFactory, $driverCollection, $configuration);

        $result = $factory->get('tl_foo');

        $this->assertSame($dca, $result);
    }

    public function testReturnsSingletonForEachResource(): void
    {
        $dataFoo = ['config' => []];
        $dataBar = ['config' => ['foo' => 'bar']];

        $schemaFactory = $this->createMock(SchemaFactory::class);
        $driverCollection = $this->createMock(DriverCollection::class);
        $configuration = $this->createMock(ConfigurationProviderInterface::class);
        $driver = $this->createMock(DriverInterface::class);
        $dcaFoo = $this->createMock(Dca::class);
        $dcaBar = $this->createMock(Dca::class);

        $configuration
            ->expects($this->exactly(2))
            ->method('getConfiguration')
            ->withConsecutive(
                ['tl_foo', $driver],
                ['tl_bar', $driver],
            )
            ->willReturnOnConsecutiveCalls($dataFoo, $dataBar)
        ;

        $driverCollection
            ->expects($this->exactly(2))
            ->method('getDriverForResource')
            ->withConsecutive(['tl_foo'], ['tl_bar'])
            ->willReturn($driver)
        ;

        $schemaFactory
            ->expects($this->exactly(2))
            ->method('createSchema')
            ->withConsecutive(
                ['tl_foo', Dca::class, new Data($dataFoo)],
                ['tl_bar', Dca::class, new Data($dataBar)],
            )
            ->willReturnOnConsecutiveCalls($dcaFoo, $dcaBar)
        ;

        $factory = new DcaFactory($schemaFactory, $driverCollection, $configuration);

        $resultA = $factory->get('tl_foo');
        $resultB = $factory->get('tl_foo');
        $resultC = $factory->get('tl_bar');

        $this->assertSame($dcaFoo, $resultA);
        $this->assertSame($resultA, $resultB);
        $this->assertSame($dcaBar, $resultC);
    }
}
