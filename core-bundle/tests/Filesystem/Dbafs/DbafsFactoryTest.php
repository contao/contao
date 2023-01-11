<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Tests\Filesystem\Dbafs;

use Contao\CoreBundle\Filesystem\Dbafs\Dbafs;
use Contao\CoreBundle\Filesystem\Dbafs\DbafsFactory;
use Contao\CoreBundle\Filesystem\Dbafs\Hashing\HashGeneratorInterface;
use Contao\CoreBundle\Filesystem\VirtualFilesystemInterface;
use Contao\CoreBundle\Tests\TestCase;
use Doctrine\DBAL\Connection;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

class DbafsFactoryTest extends TestCase
{
    public function testCreatesDbafsInstance(): void
    {
        $connection = $this->createMock(Connection::class);
        $eventDispatcher = $this->createMock(EventDispatcherInterface::class);
        $filesystem = $this->createMock(VirtualFilesystemInterface::class);
        $hashGenerator = $this->createMock(HashGeneratorInterface::class);

        $factory = new DbafsFactory($connection, $eventDispatcher);
        $dbafs = $factory($filesystem, $hashGenerator, 'tl_foo');

        $expectedValues = [
            'filesystem' => $filesystem,
            'hashGenerator' => $hashGenerator,
            'connection' => $connection,
            'table' => 'tl_foo',
        ];

        $reflection = new \ReflectionClass(Dbafs::class);

        foreach ($expectedValues as $propertyName => $value) {
            $property = $reflection->getProperty($propertyName);

            $this->assertSame($value, $property->getValue($dbafs));
        }
    }
}
