<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Tests\EventListener\InsertTags;

use Contao\Config;
use Contao\CoreBundle\EventListener\InsertTags\DateFormatListener;
use Contao\CoreBundle\Tests\TestCase;
use Contao\Date;
use Contao\PageModel;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

class DateFormatListenerTest extends TestCase
{
    public function testFormatsDate(): void
    {
        $dateAdapter = $this->mockAdapter(['parse']);
        $dateAdapter
            ->expects($this->once())
            ->method('parse')
            ->with('d.m.Y', strtotime('2020-05-26'))
            ->willReturn('26.05.2020')
        ;

        $framework = $this->mockContaoFramework([Date::class => $dateAdapter]);

        $listener = new DateFormatListener($framework, new RequestStack());

        $this->assertSame('26.05.2020', $listener('date_format::2020-05-26::d.m.Y'));
    }

    public function testFormatsTimestamp(): void
    {
        $timestamp = 1590451200;

        $dateAdapter = $this->mockAdapter(['parse']);
        $dateAdapter
            ->expects($this->once())
            ->method('parse')
            ->with('c', $timestamp)
            ->willReturn('2020-05-26T00:00:00+00:00')
        ;

        $framework = $this->mockContaoFramework([Date::class => $dateAdapter]);

        $listener = new DateFormatListener($framework, new RequestStack());

        $this->assertSame('2020-05-26T00:00:00+00:00', $listener('date_format::'.$timestamp.'::c'));
    }

    public function testUsesConfigFormat(): void
    {
        $dateAdapter = $this->mockAdapter(['parse']);
        $dateAdapter
            ->expects($this->once())
            ->method('parse')
            ->with('d.m.Y H:i', strtotime('2020-05-26'))
            ->willReturn('26.05.2020 00:00')
        ;

        $configAdapter = $this->mockAdapter(['get']);
        $configAdapter
            ->expects($this->once())
            ->method('get')
            ->with('datimFormat')
            ->willReturn('d.m.Y H:i')
        ;

        $framework = $this->mockContaoFramework([
            Date::class => $dateAdapter,
            Config::class => $configAdapter,
        ]);

        $requestStack = new RequestStack();
        $requestStack->push(new Request());

        $listener = new DateFormatListener($framework, $requestStack);

        $this->assertSame('26.05.2020 00:00', $listener('date_format::2020-05-26'));
    }

    public function testUsesPageFormat(): void
    {
        $dateAdapter = $this->mockAdapter(['parse']);
        $dateAdapter
            ->expects($this->once())
            ->method('parse')
            ->with('d.m.Y H:i', strtotime('2020-05-26'))
            ->willReturn('26.05.2020 00:00')
        ;

        $framework = $this->mockContaoFramework([Date::class => $dateAdapter]);

        $pageModel = $this->mockClassWithProperties(PageModel::class, ['datimFormat' => 'd.m.Y H:i']);

        $request = new Request();
        $request->attributes->set('pageModel', $pageModel);

        $requestStack = new RequestStack();
        $requestStack->push($request);

        $listener = new DateFormatListener($framework, $requestStack);

        $this->assertSame('26.05.2020 00:00', $listener('date_format::2020-05-26'));
    }

    public function testReturnsInvalidDate(): void
    {
        $listener = new DateFormatListener($this->mockContaoFramework(), new RequestStack());

        $this->assertSame('foobar', $listener('date_format::foobar::d.m.Y'));
    }

    public function testReturnsEmptyWithoutDate(): void
    {
        $listener = new DateFormatListener($this->mockContaoFramework(), new RequestStack());

        $this->assertSame('', $listener('date_format'));
    }
}
