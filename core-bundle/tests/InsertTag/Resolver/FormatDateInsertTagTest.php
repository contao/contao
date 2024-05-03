<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Tests\InsertTag\Resolver;

use Contao\Config;
use Contao\CoreBundle\Framework\ContaoFramework;
use Contao\CoreBundle\InsertTag\InsertTagParser;
use Contao\CoreBundle\InsertTag\OutputType;
use Contao\CoreBundle\InsertTag\ResolvedInsertTag;
use Contao\CoreBundle\InsertTag\ResolvedParameters;
use Contao\CoreBundle\InsertTag\Resolver\FormatDateInsertTag;
use Contao\CoreBundle\Tests\TestCase;
use Contao\Date;
use Contao\InsertTags;
use Contao\PageModel;
use Doctrine\Common\Annotations\AnnotationRegistry;
use Doctrine\Common\Annotations\DocParser;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpKernel\Fragment\FragmentHandler;

class FormatDateInsertTagTest extends TestCase
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
        $listener = new FormatDateInsertTag($this->getFramework(), new RequestStack());

        $parser = new InsertTagParser(
            $this->createMock(ContaoFramework::class),
            $this->createMock(LoggerInterface::class),
            $this->createMock(FragmentHandler::class),
            $this->createMock(RequestStack::class),
            (new \ReflectionClass(InsertTags::class))->newInstanceWithoutConstructor(),
        );

        /** @var ResolvedInsertTag $tag */
        $tag = $parser->parseTag($insertTag);

        $result = match ($tag->getName()) {
            'format_date' => $listener->replaceFormatDate($tag),
            'convert_date' => $listener->replaceConvertDate($tag),
            default => throw new \LogicException(),
        };

        $this->assertSame($expected, $result->getValue());
        $this->assertSame(OutputType::text, $result->getOutputType());
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
        $listener = new FormatDateInsertTag($framework, new RequestStack());

        $tag = new ResolvedInsertTag('format_date', new ResolvedParameters(['2020-05-26']), []);
        $this->assertSame('26.05.2020 00:00', $listener->replaceFormatDate($tag)->getValue());

        $tag = new ResolvedInsertTag('convert_date', new ResolvedParameters(['2020-05-26', 'Y-m-d', 'datim']), []);
        $this->assertSame('26.05.2020 00:00', $listener->replaceConvertDate($tag)->getValue());
    }

    public function testUsesPageFormat(): void
    {
        $pageModel = $this->mockClassWithProperties(PageModel::class, ['datimFormat' => 'd.m.Y H:i']);

        $request = new Request();
        $request->attributes->set('pageModel', $pageModel);

        $requestStack = new RequestStack();
        $requestStack->push($request);

        $listener = new FormatDateInsertTag($this->getFramework(), $requestStack);

        $tag = new ResolvedInsertTag('format_date', new ResolvedParameters(['2020-05-26']), []);
        $this->assertSame('26.05.2020 00:00', $listener->replaceFormatDate($tag)->getValue());

        $tag = new ResolvedInsertTag('convert_date', new ResolvedParameters(['2020-05-26', 'Y-m-d', 'datim']), []);
        $this->assertSame('26.05.2020 00:00', $listener->replaceConvertDate($tag)->getValue());
    }

    public static function getConvertedInsertTags(): iterable
    {
        yield ['format_date::2020-05-26::d.m.Y', '26.05.2020'];
        yield ['format_date::'.strtotime('2020-05-26T00:00:00+00:00').'::c', '2020-05-26T00:00:00+00:00'];
        yield ['convert_date::2020-05-26T00:00:00+00:00::Y-m-d\TH:i:sT::j. F Y, H:i:s, P', 'May 26th 2020, 00:00:00, +00:00'];

        yield ['format_date::foobar::d.m.Y', 'foobar'];
        yield ['convert_date::foobar::d.m.Y::datim', 'foobar'];
        yield ['convert_date::2020-05-26::foobar::datim', '2020-05-26'];

        yield ['format_date', ''];
        yield ['convert_date', ''];
        yield ['convert_date::2020-05-26', ''];
        yield ['convert_date::2020-05-26::Y-m-d', ''];
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

        return $this->mockContaoFramework([Date::class => $dateAdapter, ...$adapters]);
    }
}
