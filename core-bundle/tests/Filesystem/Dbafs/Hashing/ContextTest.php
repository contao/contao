<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Tests\Filesystem\Dbafs\Hashing;

use Contao\CoreBundle\Filesystem\Dbafs\Hashing\Context;
use Contao\CoreBundle\Tests\TestCase;

class ContextTest extends TestCase
{
    public function testDefaultValues(): void
    {
        $context = new Context();

        $this->assertFalse($context->canSkipHashing());
        $this->assertNull($context->getLastModified());

        $context->setHash('foo');

        $this->assertSame('foo', $context->getResult());
        $this->assertFalse($context->lastModifiedChanged());
    }

    public function testDisallowsSkippingIfNoFallbackIsSet(): void
    {
        $context = new Context();

        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('Hashing may not be skipped for the current resource.');

        $context->skipHashing();
    }

    public function testSkipHashing(): void
    {
        $context = new Context('foo');

        $this->assertTrue($context->canSkipHashing());

        $context->skipHashing();

        $this->assertSame('foo', $context->getResult());
    }

    public function testGuardsAgainstNotSettingAResult(): void
    {
        $context = new Context();

        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('No result has been set for this hashing context.');

        $context->getResult();
    }

    public function testSetLastModified(): void
    {
        $context = new Context(null, 123450);

        $this->assertSame(123450, $context->getLastModified());
        $this->assertFalse($context->lastModifiedChanged());

        $context->updateLastModified(234560);

        $this->assertTrue($context->lastModifiedChanged());
        $this->assertSame(234560, $context->getLastModified());
    }
}
