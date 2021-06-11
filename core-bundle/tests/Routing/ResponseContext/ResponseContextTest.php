<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Tests\Routing\ResponseContext;

use Contao\CoreBundle\ContaoCoreBundle;
use Contao\CoreBundle\Routing\ResponseContext\HtmlHeadBag\HtmlHeadBag;
use Contao\CoreBundle\Routing\ResponseContext\ResponseContext;
use Contao\NewsBundle\ContaoNewsBundle;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpKernel\Bundle\Bundle;
use Symfony\Component\HttpKernel\Bundle\BundleInterface;

class ResponseContextTest extends TestCase
{
    public function testCanAddAndGetServices(): void
    {
        $context = new ResponseContext();

        $this->assertFalse($context->has(HtmlHeadBag::class));

        $context->add(new HtmlHeadBag());

        $this->assertTrue($context->has(HtmlHeadBag::class));
        $this->assertInstanceOf(HtmlHeadBag::class, $context->get(HtmlHeadBag::class));
    }

    public function testLazyServices(): void
    {
        $context = new ResponseContext();

        $this->assertFalse($context->has(HtmlHeadBag::class));

        $context->addLazy(HtmlHeadBag::class, static function () { return new HtmlHeadBag(); });

        $this->assertTrue($context->has(HtmlHeadBag::class));
        $this->assertInstanceOf(HtmlHeadBag::class, $context->get(HtmlHeadBag::class));
    }

    public function testGettingANonExistentServiceThrows(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        $context = new ResponseContext();
        $context->get(HtmlHeadBag::class);
    }

    public function testInterfacesAndParentsAreAutomaticallyAddedAsAliases(): void
    {
        $context = new ResponseContext();

        // Using some unrelated classes here so we don't have to create nonsense classes implementing nonsense
        // interfaces here. We took classes that are very unlikely to change.
        $coreBundle = new ContaoCoreBundle();
        $newsBundle = new ContaoNewsBundle();

        $context->add($coreBundle);

        $this->assertSame($coreBundle, $context->get(ContaoCoreBundle::class));
        $this->assertSame($coreBundle, $context->get(BundleInterface::class));
        $this->assertSame($coreBundle, $context->get(Bundle::class));

        $context->add($newsBundle);

        $this->assertSame($coreBundle, $context->get(ContaoCoreBundle::class));
        $this->assertSame($newsBundle, $context->get(ContaoNewsBundle::class));
        $this->assertSame($newsBundle, $context->get(BundleInterface::class)); // NewsBundle was added later
        $this->assertSame($newsBundle, $context->get(Bundle::class)); // NewsBundle was added later
    }

    public function testHeaderBagIsInitializedCompletelyEmpty(): void
    {
        $context = new ResponseContext();

        $this->assertCount(0, $context->getHeaderBag()->all());
    }
}
