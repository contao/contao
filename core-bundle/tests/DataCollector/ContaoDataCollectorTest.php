<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Tests\DataCollector;

use Contao\ContentImage;
use Contao\ContentText;
use Contao\CoreBundle\ContaoCoreBundle;
use Contao\CoreBundle\DataCollector\ContaoDataCollector;
use Contao\CoreBundle\Routing\PageFinder;
use Contao\CoreBundle\Security\Authentication\Token\TokenChecker;
use Contao\CoreBundle\Tests\TestCase;
use Contao\LayoutModel;
use Contao\Model;
use Contao\Model\Registry;
use Contao\PageModel;
use Contao\System;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class ContaoDataCollectorTest extends TestCase
{
    protected function tearDown(): void
    {
        $this->resetStaticProperties([Model::class, Registry::class]);

        parent::tearDown();
    }

    public function testCollectsDataInBackEnd(): void
    {
        $GLOBALS['TL_DEBUG'] = [
            'classes_set' => [System::class],
            'classes_aliased' => ['ContentText' => ContentText::class],
            'classes_composerized' => ['ContentImage' => ContentImage::class],
            'additional_data' => 'data',
        ];

        $collector = $this->getContaoDataCollector();
        $collector->collect(new Request(), new Response());

        $this->assertSame(['ContentText' => ContentText::class], $collector->getClassesAliased());
        $this->assertSame(['ContentImage' => ContentImage::class], $collector->getClassesComposerized());

        $version = ContaoCoreBundle::getVersion();

        $this->assertSame(
            [
                'version' => $version,
                'framework' => true,
                'models' => 0,
                'frontend' => false,
                'preview' => false,
                'layout' => '',
                'template' => '',
            ],
            $collector->getSummary(),
        );

        $this->assertSame($version, $collector->getContaoVersion());
        $this->assertSame([System::class], $collector->getClassesSet());
        $this->assertSame(['additional_data' => 'data'], $collector->getAdditionalData());
        $this->assertSame('contao', $collector->getName());

        unset($GLOBALS['TL_DEBUG']);
    }

    public function testCollectsDataInFrontEnd(): void
    {
        $layout = $this->mockClassWithProperties(LayoutModel::class);
        $layout->name = 'Default';
        $layout->id = 2;
        $layout->template = 'fe_page';

        $adapter = $this->mockConfiguredAdapter(['findById' => $layout]);
        $framework = $this->mockContaoFramework([LayoutModel::class => $adapter]);

        $page = $this->mockClassWithProperties(PageModel::class);
        $page->id = 2;
        $page->layoutId = 2;

        $pageFinder = $this->createMock(PageFinder::class);
        $pageFinder
            ->method('getCurrentPage')
            ->willReturn($page)
        ;

        $collector = $this->getContaoDataCollector(pageFinder: $pageFinder);
        $collector->setFramework($framework);
        $collector->collect(new Request(), new Response());

        $this->assertSame(
            [
                'version' => ContaoCoreBundle::getVersion(),
                'framework' => false,
                'models' => 0,
                'frontend' => true,
                'preview' => false,
                'layout' => 'Default (ID 2)',
                'template' => 'fe_page',
            ],
            $collector->getSummary(),
        );

        $collector->reset();

        $this->assertSame([], $collector->getSummary());
    }

    public function testSetsTheFrontendPreviewFromTokenChecker(): void
    {
        $layout = $this->mockClassWithProperties(LayoutModel::class);
        $layout->name = 'Default';
        $layout->id = 2;
        $layout->template = 'fe_page';

        $adapter = $this->mockConfiguredAdapter(['findById' => $layout]);
        $framework = $this->mockContaoFramework([LayoutModel::class => $adapter]);

        $page = $this->mockClassWithProperties(PageModel::class);
        $page->id = 2;
        $page->layoutId = 2;

        $pageFinder = $this->createMock(PageFinder::class);
        $pageFinder
            ->method('getCurrentPage')
            ->willReturn($page)
        ;

        $tokenChecker = $this->createMock(TokenChecker::class);
        $tokenChecker
            ->expects($this->once())
            ->method('isPreviewMode')
            ->willReturn(true)
        ;

        $collector = $this->getContaoDataCollector($tokenChecker, $pageFinder);
        $collector->setFramework($framework);
        $collector->collect(new Request(), new Response());

        $this->assertSame(
            [
                'version' => ContaoCoreBundle::getVersion(),
                'framework' => false,
                'models' => 0,
                'frontend' => true,
                'preview' => true,
                'layout' => 'Default (ID 2)',
                'template' => 'fe_page',
            ],
            $collector->getSummary(),
        );
    }

    public function testReturnsAnEmptyArrayIfTheKeyIsUnknown(): void
    {
        $collector = $this->getContaoDataCollector();
        $method = new \ReflectionMethod($collector, 'getData');

        $this->assertSame([], $method->invokeArgs($collector, ['foo']));
    }

    private function getContaoDataCollector(TokenChecker|null $tokenChecker = null, PageFinder|null $pageFinder = null): ContaoDataCollector
    {
        return new ContaoDataCollector(
            $tokenChecker ?? $this->createMock(TokenChecker::class),
            $pageFinder ?? $this->createMock(PageFinder::class),
        );
    }
}
