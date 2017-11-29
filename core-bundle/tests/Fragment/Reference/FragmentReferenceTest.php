<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * Copyright (c) 2005-2017 Leo Feyer
 *
 * @license LGPL-3.0+
 */

namespace Contao\CoreBundle\Tests\Fragment\Reference;

use Contao\CoreBundle\ContaoCoreBundle;
use Contao\CoreBundle\Fragment\Reference\FragmentReference;
use Contao\CoreBundle\Tests\TestCase;

class FragmentReferenceTest extends TestCase
{
    public function testCanBeInstantiated(): void
    {
        $reference = new FragmentReference('');

        $this->assertInstanceOf('Contao\CoreBundle\Fragment\Reference\FragmentReference', $reference);
        $this->assertInstanceOf('Symfony\Component\HttpKernel\Controller\ControllerReference', $reference);
    }

    public function testSetsTheDefaultScope(): void
    {
        $reference = new FragmentReference('');

        $this->assertSame(ContaoCoreBundle::SCOPE_FRONTEND, $reference->attributes['_scope']);
    }

    public function testDoesNotOverrideAGivenScope(): void
    {
        $reference = new FragmentReference('', ['_scope' => 'foobar']);

        $this->assertSame('foobar', $reference->attributes['_scope']);
    }

    public function testReadsAndWritesScopes(): void
    {
        $reference = new FragmentReference('');

        $this->assertTrue($reference->isFrontendScope());
        $this->assertSame(ContaoCoreBundle::SCOPE_FRONTEND, $reference->attributes['_scope']);

        $reference->setBackendScope();

        $this->assertTrue($reference->isBackendScope());
        $this->assertFalse($reference->isFrontendScope());
        $this->assertSame(ContaoCoreBundle::SCOPE_BACKEND, $reference->attributes['_scope']);

        $reference->setFrontendScope();

        $this->assertTrue($reference->isFrontendScope());
        $this->assertFalse($reference->isBackendScope());
        $this->assertSame(ContaoCoreBundle::SCOPE_FRONTEND, $reference->attributes['_scope']);
    }
}
