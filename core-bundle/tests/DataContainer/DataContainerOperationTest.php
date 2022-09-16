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
        $translations = ['some_label' => ['first', 'second']];
        $config = ['label' => &$translations['some_label']];

        new DataContainerOperation('test', $config, ['id' => 1], $this->createMock(DataContainer::class));

        $this->assertSame('first', $translations['some_label'][0]);
        $this->assertSame('second', $translations['some_label'][1]);
        $this->assertSame(['first', 'second'], $translations['some_label']);
    }
}
