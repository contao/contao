<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\FaqBundle\Tests\Routing;

use Contao\FaqBundle\Routing\FaqResolver;
use Contao\FaqCategoryModel;
use Contao\FaqModel;
use Contao\PageModel;
use Contao\TestCase\ContaoTestCase;

class FaqResolverTest extends ContaoTestCase
{
    public function testResolveNewsletter(): void
    {
        $target = $this->mockClassWithProperties(PageModel::class);

        $newsletterChannel = $this->mockClassWithProperties(FaqCategoryModel::class, ['jumpTo' => 42]);

        $content = $this->mockClassWithProperties(FaqModel::class);
        $content
            ->expects($this->once())
            ->method('getRelated')
            ->with('pid')
            ->willReturn($newsletterChannel)
        ;

        $pageAdapter = $this->mockAdapter(['findPublishedById']);
        $pageAdapter
            ->expects($this->once())
            ->method('findPublishedById')
            ->with(42)
            ->willReturn($target)
        ;

        $framework = $this->mockContaoFramework([PageModel::class => $pageAdapter]);

        $resolver = new FaqResolver($framework);
        $result = $resolver->resolve($content);

        $this->assertFalse($result->isRedirect());
        $this->assertSame($target, $result->content);
    }

    /**
     * @dataProvider getParametersForContentProvider
     */
    public function testGetParametersForContent(object $content, array $expected): void
    {
        $pageModel = $this->mockClassWithProperties(PageModel::class);

        $resolver = new FaqResolver($this->mockContaoFramework());

        $this->assertSame($expected, $resolver->getParametersForContent($content, $pageModel));
    }

    public function getParametersForContentProvider(): \Generator
    {
        yield 'Uses the FAQ alias' => [
            $this->mockClassWithProperties(FaqModel::class, ['id' => 42, 'alias' => 'foobar']),
            ['parameters' => '/foobar']
        ];

        yield 'Uses FAQ ID if alias is empty' => [
            $this->mockClassWithProperties(FaqModel::class, ['id' => 42, 'alias' => '']),
            ['parameters' => '/42'],
        ];

        yield 'Only supports FaqModel' => [
            $this->mockClassWithProperties(PageModel::class),
            [],
        ];
    }
}
