<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * Copyright (c) 2005-2017 Leo Feyer
 *
 * @license LGPL-3.0+
 */

namespace Contao\CoreBundle\Tests\DataCollector;

use Contao\ContentImage;
use Contao\ContentText;
use Contao\CoreBundle\DataCollector\ContaoDataCollector;
use Contao\CoreBundle\Tests\TestCase;
use Contao\LayoutModel;
use Contao\PageModel;
use Contao\System;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class ContaoDataCollectorTest extends TestCase
{
    public function testCanBeInstantiated(): void
    {
        $collector = new ContaoDataCollector([]);

        $this->assertInstanceOf('Contao\CoreBundle\DataCollector\ContaoDataCollector', $collector);
    }

    public function testCollectsDataInBackEnd(): void
    {
        $GLOBALS['TL_DEBUG'] = [
            'classes_set' => [System::class],
            'classes_aliased' => ['ContentText' => ContentText::class],
            'classes_composerized' => ['ContentImage' => ContentImage::class],
            'additional_data' => 'data',
        ];

        $collector = new ContaoDataCollector(['contao/core-bundle' => '4.0.0']);
        $collector->collect(new Request(), new Response());

        $this->assertSame(['ContentText' => ContentText::class], $collector->getClassesAliased());
        $this->assertSame(['ContentImage' => ContentImage::class], $collector->getClassesComposerized());

        $this->assertSame(
            [
                'version' => '4.0.0',
                'framework' => true,
                'models' => 5,
                'frontend' => false,
                'preview' => false,
                'layout' => '',
                'template' => '',
            ],
            $collector->getSummary()
        );

        $this->assertSame('4.0.0', $collector->getContaoVersion());
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

        $collector = new ContaoDataCollector([]);
        $collector->setFramework($framework);
        $collector->collect(new Request(), new Response());

        $this->assertSame(
            [
                'version' => '',
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
        $collector = new ContaoDataCollector([]);
        $collector->unserialize('N;');

        $this->assertSame([], $collector->getAdditionalData());
    }

    public function testReturnsAnEmptyArrayIfTheKeyIsUnknown(): void
    {
        $collector = new ContaoDataCollector([]);

        $method = new \ReflectionMethod($collector, 'getData');
        $method->setAccessible(true);

        $this->assertSame([], $method->invokeArgs($collector, ['foo']));
    }
}
