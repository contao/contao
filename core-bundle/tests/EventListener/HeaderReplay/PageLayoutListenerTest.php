<?php

/*
 * This file is part of Contao.
 *
 * Copyright (c) 2005-2017 Leo Feyer
 *
 * @license LGPL-3.0+
 */

namespace Contao\CoreBundle\Tests\EventListener\HeaderReplay;

use Contao\CoreBundle\EventListener\HeaderReplay\PageLayoutListener;
use Contao\CoreBundle\Framework\Adapter;
use Contao\CoreBundle\Tests\TestCase;
use Contao\Environment;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Terminal42\HeaderReplay\Event\HeaderReplayEvent;

/**
 * Tests the PageLayoutListener class.
 *
 * @author Yanick Witschi <https://github.com/toflar>
 */
class PageLayoutListenerTest extends TestCase
{
    /**
     * Tests the object instantiation.
     */
    public function testCanBeInstantiated()
    {
        $listener = new PageLayoutListener($this->mockScopeMatcher(), $this->mockContaoFramework());

        $this->assertInstanceOf('Contao\CoreBundle\EventListener\HeaderReplay\PageLayoutListener', $listener);
    }

    /**
     * Tests that no header is added outside the Contao front end scope.
     */
    public function testOnReplayWithNoFrontendScope()
    {
        $event = new HeaderReplayEvent(new Request(), new ResponseHeaderBag());

        $listener = new PageLayoutListener($this->mockScopeMatcher(), $this->mockContaoFramework());
        $listener->onReplay($event);

        $this->assertArrayNotHasKey('contao-page-layout', $event->getHeaders()->all());
    }

    /**
     * Tests all combinations of user agent result, TL_VIEW cookie value and checks if the
     * header value is set correctly.
     *
     * @param bool        $agentIsMobile
     * @param string|null $tlViewCookie
     * @param string      $expectedHeaderValue
     *
     * @dataProvider onReplayProvider
     */
    public function testOnReplay($agentIsMobile, $tlViewCookie, $expectedHeaderValue)
    {
        $envAdapter = $this
            ->getMockBuilder(Adapter::class)
            ->disableOriginalConstructor()
            ->setMethods(['get'])
            ->getMock()
        ;

        $envAdapter
            ->method('get')
            ->willReturnCallback(function ($key) use ($agentIsMobile) {
                switch ($key) {
                    case 'agent':
                        return (object) ['mobile' => $agentIsMobile];

                    default:
                        return null;
                }
            })
        ;

        $request = new Request();
        $request->attributes->set('_scope', 'frontend');

        if (null !== $tlViewCookie) {
            $request->cookies->set('TL_VIEW', $tlViewCookie);
        }

        $event = new HeaderReplayEvent($request, new ResponseHeaderBag());

        $listener = new PageLayoutListener(
            $this->mockScopeMatcher(),
            $this->mockContaoFramework(null, null, [Environment::class => $envAdapter])
        );

        $listener->onReplay($event);

        $this->assertSame($expectedHeaderValue, $event->getHeaders()->get('Contao-Page-Layout'));
    }

    /**
     * Provides the data for the testOnReplayWithNoFrontendScope test.
     *
     * @return array
     */
    public function onReplayProvider()
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
}
