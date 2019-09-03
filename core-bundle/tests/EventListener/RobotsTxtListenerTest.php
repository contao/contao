<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Tests\EventListener;

use Contao\CoreBundle\Event\RobotsTxtEvent;
use Contao\CoreBundle\EventListener\RobotsTxtListener;
use Contao\CoreBundle\Tests\TestCase;
use Contao\PageModel;
use Symfony\Component\HttpFoundation\Request;
use webignition\RobotsTxt\File\Parser;

class RobotsTxtListenerTest extends TestCase
{
    /**
     * @dataProvider disallowProvider
     */
    public function testRobotsTxt(string $providedRobotsTxt, string $expectedRobotsTxt): void
    {
        $rootPage = $this->mockClassWithProperties(PageModel::class);
        $rootPage->id = 42;
        $rootPage->fallback = '1';
        $rootPage->dns = 'www.foobar.com';

        $otherRootPage = $this->mockClassWithProperties(PageModel::class);
        $otherRootPage->id = 99;
        $otherRootPage->fallback = '';
        $otherRootPage->dns = 'www.foobar.com';
        $otherRootPage->createSitemap = '1';
        $otherRootPage->sitemapName = 'sitemap-name';
        $otherRootPage->useSSL = '1';

        $pageModelAdapter = $this->mockAdapter(['findPublishedByHostname']);
        $pageModelAdapter
            ->expects($this->once())
            ->method('findPublishedByHostname')
            ->willReturn([$rootPage, $otherRootPage])
        ;

        $framework = $this->mockContaoFramework([PageModel::class => $pageModelAdapter]);
        $framework
            ->expects($this->once())
            ->method('initialize')
        ;

        $parser = new Parser();
        $parser->setSource($providedRobotsTxt);
        $file = $parser->getFile();

        $event = new RobotsTxtEvent($file, new Request(), $rootPage);

        $listener = new RobotsTxtListener($framework);
        $listener->onRobotsTxt($event);

        $this->assertSame($expectedRobotsTxt, (string) $event->getFile());
    }

    public function disallowProvider(): \Generator
    {
        yield 'Empty robots.txt content in root page' => [
            '',
            "user-agent:*\ndisallow:/contao$\ndisallow:/contao?\ndisallow:/contao/\n\nsitemap:https://www.foobar.com/share/sitemap-name.xml",
        ];

        yield 'Tests merging with existing user-agent' => [
            "user-agent:*\nallow:/",
            "user-agent:*\nallow:/\ndisallow:/contao$\ndisallow:/contao?\ndisallow:/contao/\n\nsitemap:https://www.foobar.com/share/sitemap-name.xml",
        ];

        yield 'Tests works with specific user-agent' => [
            "user-agent:googlebot\nallow:/",
            "user-agent:googlebot\nallow:/\ndisallow:/contao$\ndisallow:/contao?\ndisallow:/contao/\n\nuser-agent:*\ndisallow:/contao$\ndisallow:/contao?\ndisallow:/contao/\n\nsitemap:https://www.foobar.com/share/sitemap-name.xml",
        ];
    }
}
