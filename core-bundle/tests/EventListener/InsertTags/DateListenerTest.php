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
use Contao\CoreBundle\EventListener\InsertTags\DateListener;
use Contao\CoreBundle\Framework\ContaoFramework;
use Contao\CoreBundle\Tests\TestCase;
use Contao\Date;
use Contao\PageModel;
use Doctrine\Common\Annotations\AnnotationRegistry;
use Doctrine\Common\Annotations\DocParser;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

class DateListenerTest extends TestCase
{
    protected function tearDown(): void
    {
        $this->resetStaticProperties([[AnnotationRegistry::class, ['failedToAutoload']], DocParser::class]);

        parent::tearDown();
    }

    /**
     * @dataProvider getConvertedInsertTags
     */
    public function testReplacedInsertTag(string $insertTag, string|false $expected): void
    {
        $listener = new DateListener($this->getFramework(), new RequestStack());

        $this->assertSame($expected, $listener($insertTag));
    }

    public function testUsesConfigFormat(): void
    {
        $configAdapter = $this->mockAdapter(['get']);
        $configAdapter
            ->expects($this->exactly(2))
            ->method('get')
            ->with('datimFormat')
            ->willReturn('d.m.Y H:i')
        ;

        $framework = $this->getFramework([Config::class => $configAdapter]);
        $listener = new DateListener($framework, new RequestStack());

        $this->assertSame('26.05.2020 00:00', $listener('format_date::2020-05-26'));
        $this->assertSame('26.05.2020 00:00', $listener('convert_date::2020-05-26::Y-m-d::datim'));
    }

    public function testUsesPageFormat(): void
    {
        $pageModel = $this->mockClassWithProperties(PageModel::class, ['datimFormat' => 'd.m.Y H:i']);

        $request = new Request();
        $request->attributes->set('pageModel', $pageModel);

        $requestStack = new RequestStack();
        $requestStack->push($request);

        $listener = new DateListener($this->getFramework(), $requestStack);

        $this->assertSame('26.05.2020 00:00', $listener('format_date::2020-05-26'));
        $this->assertSame('26.05.2020 00:00', $listener('convert_date::2020-05-26::Y-m-d::datim'));
    }

    public function getConvertedInsertTags(): \Generator
    {
        yield ['format_date::2020-05-26::d.m.Y', '26.05.2020'];
        yield ['format_date::'.strtotime('2020-05-26T00:00:00+00:00').'::c', '2020-05-26T00:00:00+00:00'];
        yield ['convert_date::2020-05-26T00:00:00+00:00::Y-m-d\TH:i:sT::j. F Y, H:i:s, P', 'May 26th 2020, 00:00:00, +00:00'];

        yield ['format_date::foobar::d.m.Y', 'foobar'];
        yield ['convert_date::foobar::d.m.Y::datim', 'foobar'];
        yield ['convert_date::2020-05-26::foobar::datim', '2020-05-26'];

        yield ['format_date', false];
        yield ['convert_date', false];
        yield ['convert_date::2020-05-26', false];
        yield ['convert_date::2020-05-26::Y-m-d', false];
    }

    private function getFramework(array $adapters = []): ContaoFramework
    {
        $dateAdapter = $this->mockAdapter(['parse']);
        $dateAdapter
            ->method('parse')
            ->willReturnMap([
                ['d.m.Y', strtotime('2020-05-26'), '26.05.2020'],
                ['d.m.Y H:i', strtotime('2020-05-26'), '26.05.2020 00:00'],
                ['c', strtotime('2020-05-26T00:00:00+00:00'), '2020-05-26T00:00:00+00:00'],
                ['j. F Y, H:i:s, P', strtotime('2020-05-26T00:00:00+00:00'), 'May 26th 2020, 00:00:00, +00:00'],
            ])
        ;

        return $this->mockContaoFramework(array_merge([Date::class => $dateAdapter], $adapters));
    }
}
