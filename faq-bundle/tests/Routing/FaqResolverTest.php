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
    public function testResolveFaq(): void
    {
        $target = $this->mockClassWithProperties(PageModel::class);
        $category = $this->mockClassWithProperties(FaqCategoryModel::class, ['jumpTo' => 42]);
        $content = $this->createMock(FaqModel::class);

        $pageAdapter = $this->mockAdapter(['findById']);
        $pageAdapter
            ->expects($this->once())
            ->method('findById')
            ->with(42)
            ->willReturn($target)
        ;

        $framework = $this->mockContaoFramework([
            PageModel::class => $pageAdapter,
            FaqCategoryModel::class => $this->mockConfiguredAdapter(['findById' => $category]),
        ]);

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

    public function getParametersForContentProvider(): iterable
    {
        yield 'Uses the FAQ alias' => [
            $this->mockClassWithProperties(FaqModel::class, ['id' => 42, 'alias' => 'foobar']),
            ['parameters' => '/foobar'],
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
