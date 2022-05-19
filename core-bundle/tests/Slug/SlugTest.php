<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Tests\Slug;

use Ausi\SlugGenerator\SlugGeneratorInterface;
use Contao\CoreBundle\Framework\ContaoFramework;
use Contao\CoreBundle\Slug\Slug;
use Contao\PageModel;
use Contao\TestCase\ContaoTestCase;

class SlugTest extends ContaoTestCase
{
    public function testGeneratesTheSlug(): void
    {
        $pageModel = $this->createMock(PageModel::class);
        $pageModel
            ->expects($this->atLeastOnce())
            ->method('getSlugOptions')
            ->willReturn([])
        ;

        $pageModelAdapter = $this->mockAdapter(['findWithDetails']);
        $pageModelAdapter
            ->expects($this->atLeastOnce())
            ->method('findWithDetails')
            ->with(123)
            ->willReturn($pageModel, null)
        ;

        $framework = $this->createMock(ContaoFramework::class);
        $framework
            ->expects($this->atLeastOnce())
            ->method('getAdapter')
            ->with(PageModel::class)
            ->willReturn($pageModelAdapter)
        ;

        $generator = $this->createMock(SlugGeneratorInterface::class);
        $generator
            ->expects($this->atLeastOnce())
            ->method('generate')
            ->willReturnArgument(0)
        ;

        $slug = new Slug($generator, $framework);

        $this->assertSame('text', $slug->generate('text', 123));
        $this->assertSame('id-123', $slug->generate('123'));
        $this->assertSame('123', $slug->generate('123', 123, null, ''));
        $this->assertSame('12.3', $slug->generate('12.3'));
        $this->assertSame('text<', $slug->generate('&#116;ext{{insert::tag}}&lt;', 123));
        $this->assertSame('text-2', $slug->generate('text', [], static fn ($alias) => 'text' === $alias));
        $this->assertSame('text-10', $slug->generate('text', [], static fn ($alias) => \strlen((string) $alias) < 7));
    }
}
