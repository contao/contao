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

        $version = $this->getContaoVersion();

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
        $properties = [
            'name' => 'Default',
            'id' => 2,
            'template' => 'fe_page',
        ];

        $layout = $this->mockClassWithProperties(LayoutModel::class, $properties);
        $adapter = $this->mockConfiguredAdapter(['findByPk' => $layout]);
        $framework = $this->mockContaoFramework([LayoutModel::class => $adapter]);

        $GLOBALS['objPage'] = $this->mockClassWithProperties(PageModel::class, ['id' => 2]);

        $collector = new ContaoDataCollector();
        $collector->setFramework($framework);
        $collector->collect(new Request(), new Response());

        $this->assertSame(
            [
                'version' => $this->getContaoVersion(),
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

    public function testReturnsAnEmtpyArrayIfTheDataIsNotAnArray(): void
    {
        $collector = new ContaoDataCollector();
        $collector->unserialize('N;');

        $this->assertSame([], $collector->getAdditionalData());
    }

    public function testReturnsAnEmptyArrayIfTheKeyIsUnknown(): void
    {
        $collector = new ContaoDataCollector();

        $method = new \ReflectionMethod($collector, 'getData');
        $method->setAccessible(true);

        $this->assertSame([], $method->invokeArgs($collector, ['foo']));
    }

    private function getContaoVersion(): string
    {
        try {
            return PackageUtil::getVersion('contao/core-bundle');
        } catch (\OutOfBoundsException $e) {
            return PackageUtil::getVersion('contao/contao');
        }
    }
}
