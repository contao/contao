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

use Contao\CoreBundle\Routing\ResponseContext\HtmlBodyBag;
use Contao\CoreBundle\Routing\ResponseContext\HtmlTag;
use PHPUnit\Framework\TestCase;

class HtmlBodyBagTest extends TestCase
{
    public function testAddsAndReplacesContent(): void
    {
        $bag = new HtmlBodyBag();
        $bag
            ->add('<script src="/first.js"></script>')
            ->add('<script src="/old.js"></script>', 'app')
            ->add('<script src="/app.js"></script>', 'app')
        ;

        $this->assertSame(
            [
                '<script src="/first.js"></script>',
                'app' => '<script src="/app.js"></script>',
            ],
            $bag->all(),
        );
        $this->assertSame($bag->all(), iterator_to_array($bag));
    }

    public function testOrdersContentBySemanticTagReference(): void
    {
        $bag = new HtmlBodyBag();
        $bag
            ->addAfter(HtmlTag::script('/app.js'), HtmlTag::script('/vendor.js'))
            ->add(HtmlTag::script('/analytics.js'), 'analytics')
            ->addBefore(HtmlTag::script('/polyfill.js'), HtmlTag::script('/vendor.js'))
            ->add(HtmlTag::script('/vendor.js'), 'vendor')
        ;

        $expectedIdentifiers = [
            'analytics',
            'script[src="/polyfill.js"]',
            'vendor',
            'script[src="/app.js"]',
        ];

        $this->assertSame($expectedIdentifiers, array_keys($bag->all()));
        $this->assertSame($expectedIdentifiers, array_keys(iterator_to_array($bag)));
        $this->assertSame('/app.js', $bag->all()['script[src="/app.js"]']->getAttributes()['src']);
    }

    public function testOrdersContentByStringIdentifierReference(): void
    {
        $bag = new HtmlBodyBag();
        $bag
            ->addAfter(HtmlTag::script('/app.js'), 'vendor', 'app')
            ->addBefore(HtmlTag::script('/polyfill.js'), 'vendor', 'polyfill')
            ->add(HtmlTag::script('/vendor.js'), 'vendor')
        ;

        $this->assertSame(
            ['polyfill', 'vendor', 'app'],
            array_keys($bag->all()),
        );
    }

    public function testClearsTheOrderConstraintWhenReplacingContent(): void
    {
        $bag = new HtmlBodyBag();
        $bag
            ->addAfter('<script src="/old.js"></script>', 'vendor', 'app')
            ->add('<script src="/app.js"></script>', 'app')
            ->add('<script src="/vendor.js"></script>', 'vendor')
        ;

        $this->assertSame(
            [
                'app' => '<script src="/app.js"></script>',
                'vendor' => '<script src="/vendor.js"></script>',
            ],
            $bag->all(),
        );
    }

    public function testOverridesTheOrderingOfExistingContent(): void
    {
        $bag = new HtmlBodyBag();
        $bag
            ->addAfter('<script src="/app.js"></script>', 'vendor', 'app')
            ->add('<script src="/vendor.js"></script>', 'vendor')
            ->orderBefore('app', 'vendor')
        ;

        $this->assertSame(['app', 'vendor'], array_keys($bag->all()));

        $bag->orderAfter('app', 'vendor');

        $this->assertSame(['vendor', 'app'], array_keys($bag->all()));
    }

    public function testRequiresAnIdentifierToOrderRawContent(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('An identifier is required');

        new HtmlBodyBag()->addAfter('<script></script>', 'vendor');
    }

    public function testRejectsAmbiguousSemanticReferences(): void
    {
        $bag = new HtmlBodyBag();
        $bag
            ->add(HtmlTag::script('/vendor.js'), 'first.vendor')
            ->add(HtmlTag::script('/vendor.js'), 'second.vendor')
            ->addAfter(HtmlTag::script('/app.js'), HtmlTag::script('/vendor.js'))
        ;

        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('is ambiguous');

        $bag->all();
    }
}
