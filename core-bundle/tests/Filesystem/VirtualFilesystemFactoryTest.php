<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Tests\Filesystem;

use Contao\CoreBundle\Filesystem\Dbafs\DbafsManager;
use Contao\CoreBundle\Filesystem\MountManager;
use Contao\CoreBundle\Filesystem\VirtualFilesystem;
use Contao\CoreBundle\Filesystem\VirtualFilesystemFactory;
use Contao\CoreBundle\Tests\TestCase;

class VirtualFilesystemFactoryTest extends TestCase
{
    /**
     * @dataProvider provideReadOnlyValues
     */
    public function testCreatesVirtualFilesystemInstances(bool $readonly): void
    {
        $mountManager = $this->createMock(MountManager::class);
        $dbafsManager = $this->createMock(DbafsManager::class);

        $factory = new VirtualFilesystemFactory($mountManager, $dbafsManager);
        $dbafs = $factory('some/prefix', $readonly);

        $reflection = new \ReflectionClass(VirtualFilesystem::class);

        $expectedValues = [
            'mountManager' => $mountManager,
            'dbafsManager' => $dbafsManager,
            'prefix' => 'some/prefix',
            'readonly' => $readonly,
        ];

        foreach ($expectedValues as $propertyName => $value) {
            $property = $reflection->getProperty($propertyName);
            $this->assertSame($value, $property->getValue($dbafs));
        }
    }

    public function provideReadOnlyValues(): \Generator
    {
        yield 'protected' => [true];
        yield 'accessible' => [false];
    }
}
