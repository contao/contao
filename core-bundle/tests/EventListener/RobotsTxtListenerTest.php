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
        $rootPage->useSSL = '1';

        $pageModelAdapter = $this->mockAdapter(['findPublishedFallbackByHostname']);
        $pageModelAdapter
            ->expects($this->exactly(2))
            ->method('findPublishedFallbackByHostname')
            ->willReturn($rootPage)
        ;

        $framework = $this->mockContaoFramework([PageModel::class => $pageModelAdapter]);
        $framework
            ->expects($this->exactly(2))
            ->method('initialize')
        ;

        $parser = new Parser();
        $parser->setSource($providedRobotsTxt);
        $file = $parser->getFile();

        $event = new RobotsTxtEvent($file, new Request(), $rootPage);

        $listener = new RobotsTxtListener($framework);
        $listener($event);

        // Output should be the same, if there is another listener
        $listener($event);

        $this->assertSame($expectedRobotsTxt, (string) $event->getFile());
    }

    public function disallowProvider(): \Generator
    {
        yield 'Empty robots.txt content in root page' => [
            '',
            <<<'EOF'
                user-agent:*
                disallow:/contao/

                sitemap:https://www.foobar.com/sitemap.xml
                EOF
        ];

        yield 'Tests merging with existing user-agent' => [
            <<<'EOF'
                user-agent:*
                allow:/
                EOF
            ,
            <<<'EOF'
                user-agent:*
                allow:/
                disallow:/contao/

                sitemap:https://www.foobar.com/sitemap.xml
                EOF
        ];

        yield 'Tests works with specific user-agent' => [
            <<<'EOF'
                user-agent:googlebot
                allow:/
                EOF
            ,
            <<<'EOF'
                user-agent:googlebot
                allow:/
                disallow:/contao/

                user-agent:*
                disallow:/contao/

                sitemap:https://www.foobar.com/sitemap.xml
                EOF
        ];
    }
}
