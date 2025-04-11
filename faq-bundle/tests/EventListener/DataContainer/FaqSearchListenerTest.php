<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\FaqBundle\Tests\EventListener\DataContainer;

use Contao\FaqBundle\EventListener\DataContainer\FaqSearchListener;
use Contao\FaqCategoryModel;
use Contao\FaqModel;
use Contao\PageModel;
use Contao\CoreBundle\Routing\ContentUrlGenerator;
use Contao\CoreBundle\Tests\TestCase;
use Contao\DataContainer;
use Contao\Search;
use Doctrine\DBAL\Connection;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class FaqSearchListenerTest extends TestCase
{
    public function testPurgesTheSearchIndexOnAliasChange(): void
    {
        $faqModel = $this->createMock(FaqModel::class);

        $search = $this->mockAdapter(['removeEntry']);
        $search
            ->expects($this->once())
            ->method('removeEntry')
            ->with('uri')
        ;

        $adapters = [
            FaqModel::class => $this->mockConfiguredAdapter(['findById' => $faqModel]),
            Search::class => $search,
        ];

        $framework = $this->mockContaoFramework($adapters);

        $urlGenerator = $this->createMock(ContentUrlGenerator::class);
        $urlGenerator
            ->expects($this->once())
            ->method('generate')
            ->with($faqModel, [], UrlGeneratorInterface::ABSOLUTE_URL)
            ->willReturn('uri')
        ;


        $dc = $this->mockClassWithProperties(DataContainer::class, ['id' => 17]);
        $dc
            ->method('getCurrentRecord')
            ->willReturn(['alias' => 'foo'])
        ;

        $listener = new FaqSearchListener(
            $framework,
            $urlGenerator
        );

        $listener->onSaveAlias('bar', $dc);
    }

    public function testDoesNotPurgeTheSearchIndexWithUnchangedAlias(): void
    {
        $faqModel = $this->createMock(FaqModel::class);

        $search = $this->mockAdapter(['removeEntry']);
        $search
            ->expects($this->never())
            ->method($this->anything())
        ;

        $adapters = [
            FaqModel::class => $this->mockConfiguredAdapter(['findById' => $faqModel]),
            Search::class => $search,
        ];

        $framework = $this->mockContaoFramework($adapters);

        $urlGenerator = $this->createMock(ContentUrlGenerator::class);
        $urlGenerator
            ->expects($this->never())
            ->method($this->anything())
        ;


        $dc = $this->mockClassWithProperties(DataContainer::class, ['id' => 17]);
        $dc
            ->method('getCurrentRecord')
            ->willReturn(['alias' => 'foo'])
        ;

        $listener = new FaqSearchListener(
            $framework,
            $urlGenerator
        );

        $listener->onSaveAlias('foo', $dc);
    }

    public function testPurgesTheSearchIndexOnRobotsChange(): void
    {
        $faqModel = $this->createMock(FaqModel::class);

        $search = $this->mockAdapter(['removeEntry']);
        $search
            ->expects($this->once())
            ->method('removeEntry')
            ->with('uri')
        ;

        $adapters = [
            FaqModel::class => $this->mockConfiguredAdapter([
                'findById' => $faqModel
            ]),
            FaqCategoryModel::class => $this->mockConfiguredAdapter([
                'findById' => [
                    $this->mockClassWithProperties(FaqCategoryModel::class, ['jumpTo' => 42]),
                ],
            ]),
            PageModel::class => $this->mockConfiguredAdapter([
                'findById' => [
                    $this->mockClassWithProperties(PageModel::class, ['robots' => 'index,follow'])
                ],
            ]),
            Search::class => $search,
        ];

        $framework = $this->mockContaoFramework($adapters);

        $urlGenerator = $this->createMock(ContentUrlGenerator::class);
        $urlGenerator
            ->expects($this->once())
            ->method('generate')
            ->with($faqModel, [], UrlGeneratorInterface::ABSOLUTE_URL)
            ->willReturn('uri')
        ;

        $dc = $this->mockClassWithProperties(DataContainer::class, ['id' => 17]);
        $dc
            ->method('getCurrentRecord')
            ->willReturn([
                'robots' => 'index,follow',
                'pid' => 5
            ])
        ;

        $listener = new FaqSearchListener(
            $framework,
            $urlGenerator
        );

        $listener->onSaveRobots('noindex,follow', $dc);
    }


/**  ###########################################################
 *
 *   ToDo: More tests are required here for robots field changes
 * 
 *   ###########################################################
 **/


    public function testPurgesTheSearchIndexOnDelete(): void
    {
        $faqModel = $this->createMock(FaqModel::class);

        $search = $this->mockAdapter(['removeEntry']);
        $search
            ->expects($this->once())
            ->method('removeEntry')
            ->with('uri')
        ;

        $adapters = [
            FaqModel::class => $this->mockConfiguredAdapter(['findById' => $faqModel]),
            Search::class => $search,
        ];

        $framework = $this->mockContaoFramework($adapters);

        $urlGenerator = $this->createMock(ContentUrlGenerator::class);
        $urlGenerator
            ->expects($this->once())
            ->method('generate')
            ->with($faqModel, [], UrlGeneratorInterface::ABSOLUTE_URL)
            ->willReturn('uri')
        ;


        $dc = $this->mockClassWithProperties(DataContainer::class, ['id' => 17]);

        $listener = new FaqSearchListener(
            $framework,
            $urlGenerator
        );

        $listener->onDelete($dc);

    }

    public function testDoesNotPurgeTheSearchIndexWithoutId(): void
    {
        $faqModel = $this->createMock(FaqModel::class);

        $search = $this->mockAdapter(['removeEntry']);
        $search
            ->expects($this->never())
            ->method($this->anything())
        ;

        $adapters = [
            FaqModel::class => $this->mockConfiguredAdapter(['findById' => $faqModel]),
            Search::class => $search,
        ];

        $framework = $this->mockContaoFramework($adapters);

        $urlGenerator = $this->createMock(ContentUrlGenerator::class);
        $urlGenerator
            ->expects($this->never())
            ->method($this->anything())
        ;

        $dc = $this->mockClassWithProperties(DataContainer::class, ['id' => null]);

        $listener = new FaqSearchListener(
            $framework,
            $urlGenerator
        );

        $listener->onDelete($dc);
    }
}
