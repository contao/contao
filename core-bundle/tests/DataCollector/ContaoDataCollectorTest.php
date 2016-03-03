<?php

/*
 * This file is part of Contao.
 *
 * Copyright (c) 2005-2016 Leo Feyer
 *
 * @license LGPL-3.0+
 */

namespace Contao\CoreBundle\Test\DataCollector;

use Contao\CoreBundle\ContaoCoreBundle;
use Contao\CoreBundle\DataCollector\ContaoDataCollector;
use Contao\CoreBundle\Test\TestCase;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Tests the ContaoDataCollector class.
 *
 * @author Leo Feyer <https://github.com/leofeyer>
 */
class ContaoDataCollectorTest extends TestCase
{
    /**
     * Tests the object instantiation.
     */
    public function testInstantiation()
    {
        $collector = new ContaoDataCollector(new ContainerBuilder(), []);

        $this->assertInstanceOf('Contao\CoreBundle\DataCollector\ContaoDataCollector', $collector);
    }

    /**
     * Tests the collect() method in the back end scope.
     */
    public function testCollectInBackendScope()
    {
        $collector = new ContaoDataCollector(
            $this->mockContainerWithContaoScopes(ContaoCoreBundle::SCOPE_BACKEND),
            ['contao/core-bundle' => '4.0.0']
        );

        $GLOBALS['TL_DEBUG'] = [
            'classes_aliased' => ['ContentText <span>Contao\ContentText</span>'],
            'classes_set' => ['Contao\System'],
            'unknown_insert_tags' => ['foo'],
            'unknown_insert_tag_flags' => ['bar'],
            'additional_data' => 'data',
        ];

        $collector->collect(new Request(), new Response());

        $this->assertEquals(
            [
                'ContentText' => [
                    'alias' => 'ContentText',
                    'original' => 'Contao\ContentText',
                ],
            ],
            $collector->getClassesAliased()
        );

        $this->assertEquals(
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

        $this->assertEquals('4.0.0', $collector->getContaoVersion());
        $this->assertEquals(['Contao\System'], $collector->getClassesSet());
        $this->assertEquals(['foo'], $collector->getUnknownInsertTags());
        $this->assertEquals(['bar'], $collector->getUnknownInsertTagFlags());
        $this->assertEquals(['additional_data' => 'data'], $collector->getAdditionalData());
        $this->assertEquals('contao', $collector->getName());

        unset($GLOBALS['TL_DEBUG']);
    }

    /**
     * Tests the collect() method in the front end scope.
     */
    public function testCollectInFrontendScope()
    {
        $collector = new ContaoDataCollector($this->mockContainerWithContaoScopes(ContaoCoreBundle::SCOPE_FRONTEND), []);

        $layout = new \stdClass();
        $layout->name = 'Default';
        $layout->id = 2;
        $layout->template = 'fe_page';

        global $objPage;

        $objPage = $this
            ->getMockBuilder('Contao\PageModel')
            ->setMethods(['getRelated'])
            ->disableOriginalConstructor()
            ->getMock()
        ;

        $objPage
            ->expects($this->any())
            ->method('getRelated')
            ->willReturn($layout)
        ;

        $collector->collect(new Request(), new Response());

        $this->assertEquals(
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
    }
}
