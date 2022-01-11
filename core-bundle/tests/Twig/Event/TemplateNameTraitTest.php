<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Tests\Twig\Event;

use Contao\CoreBundle\Tests\TestCase;
use Contao\CoreBundle\Twig\Event\TemplateNameTrait;

class TemplateNameTraitTest extends TestCase
{
    public function testGetContaoTemplateValues(): void
    {
        $class = $this->getClassUsingTrait('@Contao_Foo/bar.html.twig');

        $this->assertSame('@Contao_Foo/bar.html.twig', $class->getName());

        $this->assertTrue($class->isContaoTemplate());
        $this->assertSame('Contao_Foo', $class->getContaoNamespace());
        $this->assertSame('bar.html.twig', $class->getContaoShortName());

        $this->assertSame('html', $class->getType());
        $this->assertFalse($class->matchType(''));
        $this->assertTrue($class->matchType('xml', 'html', 'foobar'));
        $this->assertFalse($class->matchType('png', 'svg', 'jpg'));
    }

    public function testGetArbitraryTemplateValues(): void
    {
        $class = $this->getClassUsingTrait('@Foo/bar/baz.svg.twig');

        $this->assertSame('@Foo/bar/baz.svg.twig', $class->getName());

        $this->assertFalse($class->isContaoTemplate());
        $this->assertSame('', $class->getContaoNamespace());
        $this->assertSame('', $class->getContaoShortName());
        $this->assertSame('svg', $class->getType());

        $this->assertFalse($class->matchType(''));
        $this->assertFalse($class->matchType('xml', 'html', 'foobar'));
        $this->assertTrue($class->matchType('png', 'svg', 'jpg'));
    }

    public function testGetDefaultValues(): void
    {
        $class = $this->getClassUsingTrait(null);

        $this->assertSame('', $class->getName());

        $this->assertFalse($class->isContaoTemplate());
        $this->assertSame('', $class->getContaoNamespace());
        $this->assertSame('', $class->getContaoShortName());
        $this->assertSame('', $class->getType());

        $this->assertFalse($class->matchType(''));
        $this->assertFalse($class->matchType('xml', 'html', 'foobar'));
    }

    private function getClassUsingTrait(?string $name): object
    {
        return new class($name) {
            use TemplateNameTrait;

            public function __construct(?string $name)
            {
                if (null !== $name) {
                    $this->setName($name);
                }
            }
        };
    }
}
