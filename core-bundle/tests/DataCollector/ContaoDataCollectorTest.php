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
use Contao\CoreBundle\DataCollector\ContaoDataCollector;
use Contao\CoreBundle\Tests\TestCase;
use Contao\CoreBundle\Util\PackageUtil;
use Contao\LayoutModel;
use Contao\PageModel;
use Contao\System;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class ContaoDataCollectorTest extends TestCase
{
    public function testCollectsDataInBackEnd(): void
    {
        $GLOBALS['TL_DEBUG'] = [
            'classes_set' => [System::class],
            'classes_aliased' => ['ContentText' => ContentText::class],
            'classes_composerized' => ['ContentImage' => ContentImage::class],
            'additional_data' => 'data',
        ];

        $collector = new ContaoDataCollector();
        $collector->collect(new Request(), new Response());

        $this->assertSame(['ContentText' => ContentText::class], $collector->getClassesAliased());
        $this->assertSame(['ContentImage' => ContentImage::class], $collector->getClassesComposerized());

        $version = PackageUtil::getContaoVersion();

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
            $collector->getSummary()
        );

        $this->assertSame($version, $collector->getContaoVersion());
        $this->assertSame([System::class], $collector->getClassesSet());
        $this->assertSame(['additional_data' => 'data'], $collector->getAdditionalData());
        $this->assertSame('contao', $collector->getName());

        unset($GLOBALS['TL_DEBUG']);
    }

    public function testCollectsDataInFrontEnd(): void
    {
        /** @var LayoutModel&MockObject $layout */
        $layout = $this->mockClassWithProperties(LayoutModel::class);
        $layout->name = 'Default';
        $layout->id = 2;
        $layout->template = 'fe_page';

        $adapter = $this->mockConfiguredAdapter(['findByPk' => $layout]);
        $framework = $this->mockContaoFramework([LayoutModel::class => $adapter]);

        /** @var PageModel&MockObject $page */
        $page = $this->mockClassWithProperties(PageModel::class);
        $page->id = 2;
        $page->layoutId = 2;

        $GLOBALS['objPage'] = $page;

        $collector = new ContaoDataCollector();
        $collector->setFramework($framework);
        $collector->collect(new Request(), new Response());

        $this->assertSame(
            [
                'version' => PackageUtil::getContaoVersion(),
                'framework' => false,
                'models' => 0,
                'frontend' => true,
                'preview' => false,
                'layout' => 'Default (ID 2)',
                'template' => 'fe_page',
            ],
            $collector->getSummary()
        );

        $collector->reset();

        $this->assertSame([], $collector->getSummary());

        unset($GLOBALS['objPage']);
    }

    public function testHandlesMissingLayoutIdGracefully(): void
    {
        /** @var LayoutModel&MockObject $layout */
        $layout = $this->mockClassWithProperties(LayoutModel::class);
        $layout->name = 'Default';
        $layout->id = 2;
        $layout->template = 'fe_page';

        $adapter = $this->mockConfiguredAdapter(['findByPk' => $layout]);
        $framework = $this->mockContaoFramework([LayoutModel::class => $adapter]);

        /** @var PageModel&MockObject $page */
        $page = $this->mockClassWithProperties(PageModel::class);
        $page->id = 2;

        $GLOBALS['objPage'] = $page;

        $collector = new ContaoDataCollector();
        $collector->setFramework($framework);
        $collector->collect(new Request(), new Response());

        $this->assertSame(
            [
                'version' => PackageUtil::getContaoVersion(),
                'framework' => false,
                'models' => 0,
                'frontend' => true,
                'preview' => false,
                'layout' => '',
                'template' => '',
            ],
            $collector->getSummary()
        );

        $collector->reset();

        $this->assertSame([], $collector->getSummary());

        unset($GLOBALS['objPage']);
    }

    public function testReturnsAnEmptyArrayIfTheKeyIsUnknown(): void
    {
        $collector = new ContaoDataCollector();

        $method = new \ReflectionMethod($collector, 'getData');
        $method->setAccessible(true);

        $this->assertSame([], $method->invokeArgs($collector, ['foo']));
    }
}
