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
            ->add(HtmlTag::script('/app.js'))
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
                0,
            ],
            array_keys($tags),
        );

        $this->assertSame('Page title - Root title', $tags[HtmlHeadBag::TAG_TITLE]->getContent());
        $this->assertSame('https://example.com/page', $tags[HtmlHeadBag::TAG_CANONICAL]->getAttributes()['href']);
        $this->assertSame('/app.js', $tags[0]->getAttributes()['src']);
    }
}
