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
use Contao\Model;
use Contao\PageModel;
use Contao\TestCase\ContaoTestCase;
use PHPUnit\Framework\Attributes\DataProvider;

class FaqResolverTest extends ContaoTestCase
{
    public function testResolveFaq(): void
    {
        $target = $this->mockClassWithProperties(PageModel::class);
        $category = $this->mockClassWithProperties(FaqCategoryModel::class, ['jumpTo' => 42]);
        $content = $this->createStub(FaqModel::class);

        $pageAdapter = $this->createAdapterMock(['findById']);
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
     * @param class-string<Model> $class
     */
    #[DataProvider('getParametersForContentProvider')]
    public function testGetParametersForContent(string $class, array $properties, array $expected): void
    {
        $content = $this->mockClassWithProperties($class, $properties);
        $pageModel = $this->mockClassWithProperties(PageModel::class);
        $resolver = new FaqResolver($this->mockContaoFramework());

        $this->assertSame($expected, $resolver->getParametersForContent($content, $pageModel));
    }

    public static function getParametersForContentProvider(): iterable
    {
        yield 'Uses the FAQ alias' => [
            FaqModel::class,
            ['id' => 42, 'alias' => 'foobar'],
            ['parameters' => '/foobar'],
        ];

        yield 'Uses FAQ ID if alias is empty' => [
            FaqModel::class,
            ['id' => 42, 'alias' => ''],
            ['parameters' => '/42'],
        ];

        yield 'Only supports FaqModel' => [
            PageModel::class,
            [],
            [],
        ];
    }
}
