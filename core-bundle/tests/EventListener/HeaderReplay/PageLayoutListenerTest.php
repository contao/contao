<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Tests\EventListener\HeaderReplay;

use Contao\CoreBundle\EventListener\HeaderReplay\PageLayoutListener;
use Contao\CoreBundle\Tests\TestCase;
use Contao\Environment;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Terminal42\HeaderReplay\Event\HeaderReplayEvent;

class PageLayoutListenerTest extends TestCase
{
    /**
     * @dataProvider onReplayProvider
     */
    public function testAddsThePageLayoutHeader(bool $agentIsMobile, ?string $tlViewCookie, string $expectedHeaderValue): void
    {
        $adapter = $this->mockAdapter(['get']);
        $adapter
            ->method('get')
            ->willReturnCallback(
                function (string $key) use ($agentIsMobile) {
                    if ('agent' === $key) {
                        return (object) ['mobile' => $agentIsMobile];
                    }

                    return null;
                }
            )
        ;

        $framework = $this->mockContaoFramework([Environment::class => $adapter]);

        $request = new Request();
        $request->attributes->set('_scope', 'frontend');

        if (null !== $tlViewCookie) {
            $request->cookies->set('TL_VIEW', $tlViewCookie);
        }

        $event = new HeaderReplayEvent($request, new ResponseHeaderBag());

        $listener = new PageLayoutListener($this->mockScopeMatcher(), $framework);
        $listener->onReplay($event);

        $this->assertSame($expectedHeaderValue, $event->getHeaders()->get('Contao-Page-Layout'));
    }

    /**
     * @return array<string,(string|bool|null)[]>
     */
    public function onReplayProvider(): array
    {
        return [
            'No cookie -> desktop' => [false, null, 'desktop'],
            'No cookie -> mobile' => [true, null, 'mobile'],
            'Cookie mobile -> mobile when agent match' => [true, 'mobile', 'mobile'],
            'Cookie mobile -> mobile when agent does not match' => [false, 'mobile', 'mobile'],
            'Cookie desktop -> desktop when agent match' => [true, 'desktop', 'desktop'],
            'Cookie desktop -> desktop when agent does not match' => [false, 'desktop', 'desktop'],
        ];
    }

    public function testDoesNotAddThePageLayoutHeaderIfNotInFrontEndScope(): void
    {
        $event = new HeaderReplayEvent(new Request(), new ResponseHeaderBag());

        $listener = new PageLayoutListener($this->mockScopeMatcher(), $this->mockContaoFramework());
        $listener->onReplay($event);

        $this->assertArrayNotHasKey('contao-page-layout', $event->getHeaders()->all());
    }
}
