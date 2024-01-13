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
use Symfony\Bundle\WebProfilerBundle\EventListener\WebDebugToolbarListener;
use Symfony\Component\HttpFoundation\Request;
use webignition\RobotsTxt\Directive\Directive;
use webignition\RobotsTxt\DirectiveList\DirectiveList;
use webignition\RobotsTxt\File\File;
use webignition\RobotsTxt\File\Parser;
use webignition\RobotsTxt\Record\Record;

class RobotsTxtListenerTest extends TestCase
{
    /**
     * @dataProvider disallowProvider
     */
    public function testRobotsTxt(string $providedRobotsTxt, bool|null $withWebProfiler, string $expectedRobotsTxt): void
    {
        $rootPage = $this->mockClassWithProperties(PageModel::class);
        $rootPage->id = 42;
        $rootPage->fallback = true;
        $rootPage->dns = 'www.foobar.com';
        $rootPage->useSSL = true;

        $framework = $this->mockContaoFramework();
        $framework
            ->expects($this->exactly(2))
            ->method('initialize')
        ;

        $webDebugToolbar = null;

        if (\is_bool($withWebProfiler)) {
            $webDebugToolbar = $this->createMock(WebDebugToolbarListener::class);
            $webDebugToolbar
                ->expects($this->atLeastOnce())
                ->method('isEnabled')
                ->willReturn($withWebProfiler)
            ;
        }

        $parser = new Parser();
        $parser->setSource($providedRobotsTxt);

        $event = new RobotsTxtEvent($parser->getFile(), Request::create('https://www.example.org/robots.txt'), $rootPage);

        $listener = new RobotsTxtListener($framework, $webDebugToolbar);
        $listener($event);

        // Output should be the same, if there is another listener
        $listener($event);

        $this->assertSame($expectedRobotsTxt, (string) $event->getFile());
    }

    public function disallowProvider(): \Generator
    {
        yield 'Empty robots.txt content in root page' => [
            '',
            null,
            <<<'EOF'
                user-agent:*
                disallow:/contao/
                disallow:/_contao/

                sitemap:https://www.foobar.com/sitemap.xml
                EOF,
        ];

        yield 'Tests merging with existing user-agent' => [
            <<<'EOF'
                user-agent:*
                allow:/
                EOF
            ,
            null,
            <<<'EOF'
                user-agent:*
                allow:/
                disallow:/contao/
                disallow:/_contao/

                sitemap:https://www.foobar.com/sitemap.xml
                EOF,
        ];

        yield 'Tests works with specific user-agent' => [
            <<<'EOF'
                user-agent:googlebot
                allow:/
                EOF
            ,
            null,
            <<<'EOF'
                user-agent:googlebot
                allow:/
                disallow:/contao/
                disallow:/_contao/

                user-agent:*
                disallow:/contao/
                disallow:/_contao/

                sitemap:https://www.foobar.com/sitemap.xml
                EOF,
        ];

        yield 'Empty robots.txt with web profiler enabled' => [
            '',
            true,
            <<<'EOF'
                user-agent:*
                disallow:/contao/
                disallow:/_contao/
                disallow:/_profiler/
                disallow:/_wdt/

                sitemap:https://www.foobar.com/sitemap.xml
                EOF,
        ];

        yield 'Multiple user-agents with web profiler enabled' => [
            <<<'EOF'
                user-agent:googlebot
                allow:/
                EOF
            ,
            true,
            <<<'EOF'
                user-agent:googlebot
                allow:/
                disallow:/contao/
                disallow:/_contao/
                disallow:/_profiler/
                disallow:/_wdt/

                user-agent:*
                disallow:/contao/
                disallow:/_contao/
                disallow:/_profiler/
                disallow:/_wdt/

                sitemap:https://www.foobar.com/sitemap.xml
                EOF,
        ];

        yield 'Empty robots.txt with web profiler disabled' => [
            '',
            false,
            <<<'EOF'
                user-agent:*
                disallow:/contao/
                disallow:/_contao/

                sitemap:https://www.foobar.com/sitemap.xml
                EOF,
        ];
    }

    /**
     * @dataProvider routePrefixProvider
     */
    public function testHandlesDynamicRoutePrefixes(string $routePrefix): void
    {
        $rootPage = $this->mockClassWithProperties(PageModel::class);

        $directiveList = $this->createMock(DirectiveList::class);
        $directiveList
            ->expects($this->exactly(2))
            ->method('add')
            ->withConsecutive(
                [$this->callback(static fn (Directive $directive) => (string) $directive === 'disallow:'.$routePrefix.'/')],
                ['disallow:/_contao/'],
            )
        ;

        $record = $this->createMock(Record::class);
        $record
            ->method('getDirectiveList')
            ->willReturn($directiveList)
        ;

        $file = $this->createPartialMock(File::class, ['getRecords']);
        $file
            ->method('getRecords')
            ->willReturn([$record])
        ;

        $event = new RobotsTxtEvent($file, Request::create('https://www.example.org/robots.txt'), $rootPage);
        $framework = $this->mockContaoFramework();

        $listener = new RobotsTxtListener($framework, null, $routePrefix);
        $listener($event);
    }

    public function routePrefixProvider(): \Generator
    {
        yield ['/contao'];
        yield ['/admin'];
        yield ['/foo'];
    }
}
