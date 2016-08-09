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
        $collector = new ContaoDataCollector([]);

        $this->assertInstanceOf('Contao\CoreBundle\DataCollector\ContaoDataCollector', $collector);
    }

    /**
     * Tests the collect() method in the back end scope.
     */
    public function testCollectInBackendScope()
    {
        $GLOBALS['TL_DEBUG'] = [
            'classes_set' => ['Contao\System'],
            'classes_aliased' => ['ContentText' => 'Contao\ContentText'],
            'classes_composerized' => ['ContentImage' => 'Contao\ContentImage'],
            'unknown_insert_tags' => ['foo'],
            'unknown_insert_tag_flags' => ['bar'],
            'additional_data' => 'data',
        ];

        $collector = new ContaoDataCollector(['contao/core-bundle' => '4.0.0']);
        $collector->setContainer($this->mockContainerWithContaoScopes(ContaoCoreBundle::SCOPE_BACKEND));
        $collector->collect(new Request(), new Response());

        $this->assertEquals(['ContentText' => 'Contao\ContentText'], $collector->getClassesAliased());
        $this->assertEquals(['ContentImage' => 'Contao\ContentImage'], $collector->getClassesComposerized());

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
        $layout = new \stdClass();
        $layout->name = 'Default';
        $layout->id = 2;
        $layout->template = 'fe_page';

        $adapter = $this
            ->getMockBuilder('Contao\CoreBundle\Framework\Adapter')
            ->setMethods(['__call'])
            ->disableOriginalConstructor()
            ->getMock()
        ;

        $adapter
            ->expects($this->any())
            ->method('__call')
            ->willReturn($layout)
        ;

        global $objPage;

        $objPage = new \stdClass();
        $objPage->layoutId = 2;

        $collector = new ContaoDataCollector([]);
        $collector->setContainer($this->mockContainerWithContaoScopes(ContaoCoreBundle::SCOPE_FRONTEND));
        $collector->setFramework($this->mockContaoFramework(null, null, ['Contao\LayoutModel' => $adapter]));
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
