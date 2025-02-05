<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Tests\DataContainer;

use Contao\CoreBundle\DataContainer\DataContainerOperation;
use Contao\CoreBundle\Tests\TestCase;
use Contao\DataContainer;

class DataContainerOperationTest extends TestCase
{
    public function testDoesNotModifyLabelReferences(): void
    {
        /** @phpstan-var array $translations (signals PHPStan that the array shape may change) */
        $translations = ['some_label' => ['first', 'second']];
        $config = ['label' => &$translations['some_label']];

        new DataContainerOperation('test', $config, ['id' => 1], $this->createMock(DataContainer::class));

        $this->assertSame('first', $translations['some_label'][0]);
        $this->assertSame('second', $translations['some_label'][1]);
        $this->assertSame(['first', 'second'], $translations['some_label']);
    }

    public function testDisablesOperation(): void
    {
        $config = ['href' => '#foo', 'route' => 'bar', 'icon' => 'edit.svg'];

        $operation = new DataContainerOperation('test', $config, ['id' => 1], $this->createMock(DataContainer::class));

        $this->assertSame('#foo', $operation['href']);
        $this->assertSame('bar', $operation['route']);
        $this->assertSame('edit.svg', $operation['icon']);

        $operation->disable();

        $this->assertArrayNotHasKey('href', $operation);
        $this->assertArrayNotHasKey('route', $operation);
        $this->assertSame('edit--disabled.svg', $operation['icon']);
    }
}
