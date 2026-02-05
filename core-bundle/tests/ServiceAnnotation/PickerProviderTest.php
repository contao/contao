<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Tests\ServiceAnnotation;

use Contao\CoreBundle\ServiceAnnotation\PickerProvider;
use PHPUnit\Framework\TestCase;

class PickerProviderTest extends TestCase
{
    public function testReturnsTheTagName(): void
    {
        $this->expectUserDeprecationMessageMatches('/Use the #\[AsPickerProvider] attribute instead\./');

        $annotation = new PickerProvider();

        $this->assertSame('contao.picker_provider', $annotation->getName());
    }

    public function testReturnsTheArguments(): void
    {
        $annotation = new PickerProvider();
        $annotation->priority = 17;

        $this->assertSame(['priority' => 17], $annotation->getAttributes());
    }

    public function testDoesNotReturnThePriorityIfNotSet(): void
    {
        $annotation = new PickerProvider();

        $this->assertSame([], $annotation->getAttributes());
    }
}
