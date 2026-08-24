<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Tests\Routing\ResponseContext\HtmlHeadBag;

use Contao\CoreBundle\Routing\ResponseContext\HtmlHeadBag\HtmlHeadBag;
use Contao\CoreBundle\Routing\ResponseContext\HtmlTag;
use Contao\CoreBundle\String\HtmlAttributes;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;

class HtmlHeadBagTest extends TestCase
{
    public function testHeadManagerBasics(): void
    {
        $manager = new HtmlHeadBag();
        $manager->setName('foobar');
        $manager->setTitle('foobar title');
        $manager->setMetaDescription('foobar description');

        $this->assertSame('index,follow', $manager->getMetaRobots()); // Test default

        $manager->setMetaRobots('noindex,nofollow');

        $this->assertSame('foobar', $manager->getName());
        $this->assertSame('foobar title', $manager->getTitle());
        $this->assertSame('foobar description', $manager->getMetaDescription());
        $this->assertSame('noindex,nofollow', $manager->getMetaRobots());
    }

    public function testCanonicalHandling(): void
    {
        $manager = new HtmlHeadBag();

        $this->assertSame([], $manager->getKeepParamsForCanonical());

        $manager->addKeepParamsForCanonical('page');
        $manager->addKeepParamsForCanonical('page2');

        $this->assertSame(['page', 'page2'], $manager->getKeepParamsForCanonical());

        $manager->setKeepParamsForCanonical(['foo', 'page']);

        $this->assertSame(['foo', 'page'], $manager->getKeepParamsForCanonical());

        $request = Request::create('https://contao.org/foobar/page?query=test&foo=bar&baz=bak&page=12');

        $this->assertSame('https://contao.org/foobar/page?foo=bar&page=12', $manager->getCanonicalUriForRequest($request));

        $manager->setCanonicalUri('https://example.com/i-decided-myself?page=23&foo=bar');

        $this->assertSame('https://example.com/i-decided-myself?page=23&foo=bar', $manager->getCanonicalUri());
        $this->assertSame('https://example.com/i-decided-myself?foo=bar&page=23', $manager->getCanonicalUriForRequest($request));

        $manager->setCanonicalUri('//example.com/i-decided-myself?page=23&foo=bar');

        $this->assertSame('//example.com/i-decided-myself?page=23&foo=bar', $manager->getCanonicalUri());
        $this->assertSame('http://example.com/i-decided-myself?foo=bar&page=23', $manager->getCanonicalUriForRequest($request));
    }

    public function testCanonicalWithWildCards(): void
    {
        $manager = new HtmlHeadBag();
        $manager->setKeepParamsForCanonical(['foo', 'page_*']);

        $request = Request::create('https://contao.org/foobar/page?query=test&foo=bar&baz=bak&page_42=12');

        $this->assertSame('https://contao.org/foobar/page?foo=bar&page_42=12', $manager->getCanonicalUriForRequest($request));
    }

    public function testMetaTagHandling(): void
    {
        $manager = new HtmlHeadBag();

        $this->assertSame([], $manager->getMetaTags());

        $manager->addMetaTag(new HtmlAttributes()->set('property', 'og:image')->set('content', 'https://example.com/o%20"g.png'));
        $manager->addMetaTag(new HtmlAttributes()->set('name', 'foo')->set('content', 'bar'));

        $this->assertCount(2, $manager->getMetaTags());
        $this->assertSame(' property="og:image" content="https://example.com/o%20&quot;g.png" name="foo" content="bar"', implode('', $manager->getMetaTags()));

        $manager->removeMetaTag('property', 'og:image');

        $this->assertCount(1, $manager->getMetaTags());
        $this->assertSame(' name="foo" content="bar"', implode('', $manager->getMetaTags()));
        $this->assertArrayHasKey('contao.meta.additional.1', $manager->all());

        $manager->setMetaTags([]);

        $this->assertSame([], $manager->getMetaTags());
    }

    public function testCollectsAllHeadTags(): void
    {
        $manager = new HtmlHeadBag();
        $manager
            ->setTitle('Page title - Root title')
            ->setMetaDescription('Description')
            ->setCanonicalEnabled(true)
            ->addMetaTag(new HtmlAttributes(['property' => 'og:title', 'content' => 'Open Graph title']))
            ->addLinkTag(new HtmlAttributes(['rel' => 'alternate', 'href' => '/feed.xml']))
            ->add(HtmlTag::script('/app.js'), 'app.script')
        ;

        $tags = $manager->all(Request::create('https://example.com/page'));

        $this->assertSame(
            [
                HtmlHeadBag::TAG_TITLE,
                HtmlHeadBag::TAG_ROBOTS,
                HtmlHeadBag::TAG_DESCRIPTION,
                'contao.meta.additional.0',
                HtmlHeadBag::TAG_CANONICAL,
                'contao.link.additional.0',
                'app.script',
            ],
            array_keys($tags),
        );

        $this->assertSame('Page title - Root title', $tags[HtmlHeadBag::TAG_TITLE]->getContent());
        $this->assertSame('https://example.com/page', $tags[HtmlHeadBag::TAG_CANONICAL]->getAttributes()['href']);
    }

    public function testOrdersAndReplacesTagsByIdentifier(): void
    {
        $manager = new HtmlHeadBag();
        $manager
            ->addBefore(HtmlTag::meta(['name' => 'first']), HtmlHeadBag::TAG_TITLE, 'before.title')
            ->addAfter(HtmlTag::script('/after.js'), HtmlHeadBag::TAG_DESCRIPTION, 'after.description')
            ->add(HtmlTag::script('/old.js'), 'replace.me')
            ->add(HtmlTag::script('/new.js'), 'replace.me')
            ->addRaw('raw', '<meta data-raw>')
        ;

        $tags = $manager->all();
        $identifiers = array_keys($tags);

        $this->assertSame('before.title', $identifiers[0]);
        $this->assertSame(HtmlHeadBag::TAG_TITLE, $identifiers[1]);
        $this->assertSame(HtmlHeadBag::TAG_DESCRIPTION, $identifiers[3]);
        $this->assertSame('after.description', $identifiers[4]);
        $this->assertSame('/new.js', $tags['replace.me']->getAttributes()['src']);
        $this->assertSame('<meta data-raw>', $tags['raw']);
        $this->assertCount(1, array_filter($identifiers, static fn (string $identifier): bool => 'replace.me' === $identifier));
    }

    public function testOrdersRawTagsByIdentifier(): void
    {
        $manager = new HtmlHeadBag();
        $manager
            ->addRawAfter('third', '<meta data-third>', 'second')
            ->addRaw('second', '<meta data-second>')
            ->addRawBefore('first', '<meta data-first>', 'second')
        ;

        $tags = $manager->all();
        $identifiers = array_keys($tags);

        $this->assertLessThan(array_search('second', $identifiers, true), array_search('first', $identifiers, true));
        $this->assertLessThan(array_search('third', $identifiers, true), array_search('second', $identifiers, true));
    }

    public function testOverridesTheOrderingOfExistingAndCoreTags(): void
    {
        $manager = new HtmlHeadBag();
        $manager
            ->addAfter(HtmlTag::script('/app.js'), HtmlHeadBag::TAG_TITLE, 'app.script')
            ->orderBefore('app.script', HtmlHeadBag::TAG_TITLE)
            ->orderAfter(HtmlHeadBag::TAG_TITLE, HtmlHeadBag::TAG_DESCRIPTION)
        ;

        $identifiers = array_keys($manager->all());

        $this->assertLessThan(array_search(HtmlHeadBag::TAG_TITLE, $identifiers, true), array_search('app.script', $identifiers, true));
        $this->assertLessThan(array_search(HtmlHeadBag::TAG_TITLE, $identifiers, true), array_search(HtmlHeadBag::TAG_DESCRIPTION, $identifiers, true));
    }

    public function testOrdersCategorizedTagsByIdentifier(): void
    {
        $manager = new HtmlHeadBag();
        $manager
            ->addRawToHead('app', '<script src="/app.js"></script>')
            ->addRawToHead('vendor', '<script src="/vendor.js"></script>')
            ->orderAfter('app', 'vendor')
            ->orderBefore('app', 'vendor')
        ;

        $identifiers = array_keys($manager->all());

        $this->assertLessThan(array_search('contao.twig.head.vendor', $identifiers, true), array_search('contao.twig.head.app', $identifiers, true));
    }

    public function testChecksAndRemovesCategorizedTagsByIdentifier(): void
    {
        $manager = new HtmlHeadBag()
            ->addRawToHead('module', '<script src="/module.js"></script>')
            ->addRawToStylesheets('theme', '<link rel="stylesheet" href="/theme.css">')
        ;

        $this->assertTrue($manager->has('module'));
        $this->assertTrue($manager->has('theme'));

        $manager
            ->remove('module')
            ->remove('theme')
        ;

        $this->assertFalse($manager->has('module'));
        $this->assertFalse($manager->has('theme'));
        $this->assertArrayNotHasKey('contao.twig.head.module', $manager->all());
        $this->assertArrayNotHasKey('contao.twig.stylesheets.theme', $manager->all());
    }

    public function testKeepsEqualIdentifiersInSeparateCategories(): void
    {
        $manager = new HtmlHeadBag();
        $manager
            ->addRawToHead('shared', '<meta data-shared>')
            ->addRawToStylesheets('shared', '<link rel="stylesheet" href="/shared.css">')
        ;

        $tags = $manager->all();

        $this->assertSame('<meta data-shared>', $tags['contao.twig.head.shared']);
        $this->assertSame('<link rel="stylesheet" href="/shared.css">', $tags['contao.twig.stylesheets.shared']);
    }

    public function testRemovesDynamicCoreTags(): void
    {
        $manager = new HtmlHeadBag()->remove(HtmlHeadBag::TAG_ROBOTS);

        $this->assertFalse($manager->has(HtmlHeadBag::TAG_ROBOTS));
    }

    public function testChecksRequestDependentCanonicalTag(): void
    {
        $manager = new HtmlHeadBag()->setCanonicalEnabled(true);

        $this->assertFalse($manager->has(HtmlHeadBag::TAG_CANONICAL));
        $this->assertTrue($manager->has(HtmlHeadBag::TAG_CANONICAL, Request::create('https://example.com/')));
    }

    public function testDetectsOrderingCycles(): void
    {
        $manager = new HtmlHeadBag();
        $manager
            ->addAfter(HtmlTag::meta(), 'second', 'first')
            ->addAfter(HtmlTag::meta(), 'first', 'second')
        ;

        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('Cyclic array ordering constraints');

        $manager->all();
    }

    public function testAcceptsEquivalentOrderingConstraints(): void
    {
        $manager = new HtmlHeadBag();
        $manager
            ->addBefore(HtmlTag::meta(), 'second', 'first')
            ->addAfter(HtmlTag::meta(), 'first', 'second')
        ;

        $identifiers = array_keys($manager->all());

        $this->assertLessThan(array_search('second', $identifiers, true), array_search('first', $identifiers, true));
    }

    public function testOrdersNonTreeConstraints(): void
    {
        $manager = new HtmlHeadBag();
        $manager
            ->addBefore(HtmlTag::meta(), 'second', 'first')
            ->addAfter(HtmlTag::meta(), 'third', 'second')
            ->addBefore(HtmlTag::meta(), 'first', 'third')
        ;

        $identifiers = array_keys($manager->all());

        $this->assertLessThan(array_search('first', $identifiers, true), array_search('third', $identifiers, true));
        $this->assertLessThan(array_search('second', $identifiers, true), array_search('first', $identifiers, true));
    }

    public function testDerivesIdentifiersAndResolvesSemanticReferences(): void
    {
        $manager = new HtmlHeadBag();
        $manager
            ->addAfter(HtmlTag::script('/app.js'), HtmlTag::script('/vendor.js'))
            ->add(HtmlTag::script('/vendor.js', ['defer' => true]), 'vendor.script')
        ;

        $tags = $manager->all();

        $this->assertArrayHasKey('script[src="/app.js"]', $tags);
        $this->assertLessThan(
            array_search('script[src="/app.js"]', array_keys($tags), true),
            array_search('vendor.script', array_keys($tags), true),
        );
    }

    public function testRejectsAmbiguousSemanticReferences(): void
    {
        $manager = new HtmlHeadBag();
        $manager
            ->add(HtmlTag::script('/vendor.js'), 'first.vendor')
            ->add(HtmlTag::script('/vendor.js'), 'second.vendor')
            ->addAfter(HtmlTag::script('/app.js'), HtmlTag::script('/vendor.js'))
        ;

        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('The HTML tag reference "script[src=');
        $this->expectExceptionMessage('is ambiguous');

        $manager->all();
    }
}
