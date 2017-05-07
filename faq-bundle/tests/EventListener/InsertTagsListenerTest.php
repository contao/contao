<?php

/*
 * This file is part of Contao.
 *
 * Copyright (c) 2005-2017 Leo Feyer
 *
 * @license LGPL-3.0+
 */

namespace Contao\FaqBundle\Tests\EventListener;

use Contao\CoreBundle\Framework\Adapter;
use Contao\CoreBundle\Framework\ContaoFramework;
use Contao\CoreBundle\Framework\ContaoFrameworkInterface;
use Contao\FaqBundle\EventListener\InsertTagsListener;

/**
 * Tests the InsertTagsListener class.
 *
 * @author Leo Feyer <https://github.com/leofeyer>
 */
class InsertTagsListenerTest extends \PHPUnit_Framework_TestCase
{
    /**
     * Tests the object instantiation.
     */
    public function testInstantiation()
    {
        $listener = new InsertTagsListener($this->mockContaoFramework());

        $this->assertInstanceOf('Contao\FaqBundle\EventListener\InsertTagsListener', $listener);
    }

    /**
     * Tests that the listener returns a replacement string.
     */
    public function testReturnReplacementString()
    {
        $listener = new InsertTagsListener($this->mockContaoFramework());

        $this->assertSame(
            '<a href="faq/what-does-foobar-mean.html" title="What does &quot;foobar&quot; mean?">What does "foobar" mean?</a>',
            $listener->onReplaceInsertTags('faq::2')
        );

        $this->assertSame(
            '<a href="faq/what-does-foobar-mean.html" title="What does &quot;foobar&quot; mean?">',
            $listener->onReplaceInsertTags('faq_open::2')
        );

        $this->assertSame(
            'faq/what-does-foobar-mean.html',
            $listener->onReplaceInsertTags('faq_url::2')
        );

        $this->assertSame(
            'What does &quot;foobar&quot; mean?',
            $listener->onReplaceInsertTags('faq_title::2')
        );
    }

    /**
     * Tests that the listener returns false if the tag is unknown.
     */
    public function testReturnFalseIfTagUnknown()
    {
        $listener = new InsertTagsListener($this->mockContaoFramework());

        $this->assertFalse($listener->onReplaceInsertTags('link_url::2'));
    }

    /**
     * Tests that the listener returns an empty string if there is no model.
     */
    public function testReturnEmptyStringIfNoModel()
    {
        $listener = new InsertTagsListener($this->mockContaoFramework(true));

        $this->assertTrue('' === $listener->onReplaceInsertTags('faq_url::2'));
    }

    /**
     * Tests that the listener returns an empty string if there is no category model.
     */
    public function testReturnEmptyStringIfNoCategoryModel()
    {
        $listener = new InsertTagsListener($this->mockContaoFramework(false, true));

        $this->assertTrue('' === $listener->onReplaceInsertTags('faq_url::3'));
    }

    /**
     * Returns a ContaoFramework instance.
     *
     * @param bool $noFaqModel
     * @param bool $noFaqCategory
     *
     * @return ContaoFrameworkInterface
     */
    private function mockContaoFramework($noFaqModel = false, $noFaqCategory = false)
    {
        /** @var ContaoFramework|\PHPUnit_Framework_MockObject_MockObject $framework */
        $framework = $this
            ->getMockBuilder('Contao\CoreBundle\Framework\ContaoFramework')
            ->disableOriginalConstructor()
            ->setMethods(['isInitialized', 'getAdapter'])
            ->getMock()
        ;

        $framework
            ->expects($this->any())
            ->method('isInitialized')
            ->willReturn(true)
        ;

        $page = $this
            ->getMockBuilder('Contao\PageModel')
            ->setMethods(['getFrontendUrl'])
            ->disableOriginalConstructor()
            ->getMock()
        ;

        $page
            ->expects($this->any())
            ->method('getFrontendUrl')
            ->willReturn('faq/what-does-foobar-mean.html')
        ;

        $category = $this
            ->getMockBuilder('Contao\FaqCategoryModel')
            ->setMethods(['getRelated'])
            ->disableOriginalConstructor()
            ->getMock()
        ;

        $category
            ->expects($this->any())
            ->method('getRelated')
            ->willReturn($page)
        ;

        $faq = $this
            ->getMockBuilder('Contao\FaqModel')
            ->setMethods(['getRelated', '__get'])
            ->disableOriginalConstructor()
            ->getMock()
        ;

        $faq
            ->expects($this->any())
            ->method('getRelated')
            ->willReturn($noFaqCategory ? null : $category)
        ;

        $faq
            ->expects($this->any())
            ->method('__get')
            ->willReturnCallback(function ($key) {
                switch ($key) {
                    case 'id':
                        return 2;

                    case 'alias':
                        return 'what-does-foobar-mean';

                    case 'question':
                        return 'What does "foobar" mean?';

                    default:
                        return null;
                }
            })
        ;

        $faqModelAdapter = $this
            ->getMockBuilder('Contao\CoreBundle\Framework\Adapter')
            ->setMethods(['findByIdOrAlias'])
            ->setConstructorArgs(['Contao\FaqModel'])
            ->getMock()
        ;

        $faqModelAdapter
            ->expects($this->any())
            ->method('findByIdOrAlias')
            ->willReturn($noFaqModel ? null : $faq)
        ;

        $framework
            ->expects($this->any())
            ->method('getAdapter')
            ->willReturnCallback(function ($key) use ($faqModelAdapter) {
                switch ($key) {
                    case 'Contao\FaqModel':
                        return $faqModelAdapter;

                    case 'Contao\Config':
                        return $this->mockConfigAdapter();

                    default:
                        return null;
                }
            })
        ;

        return $framework;
    }

    /**
     * Mocks a config adapter.
     *
     * @return Adapter|\PHPUnit_Framework_MockObject_MockObject
     */
    private function mockConfigAdapter()
    {
        $configAdapter = $this
            ->getMockBuilder('Contao\CoreBundle\Framework\Adapter')
            ->setMethods(['isComplete', 'preload', 'getInstance', 'get'])
            ->disableOriginalConstructor()
            ->getMock()
        ;

        $configAdapter
            ->expects($this->any())
            ->method('isComplete')
            ->willReturn(true)
        ;

        $configAdapter
            ->expects($this->any())
            ->method('preload')
            ->willReturn(null)
        ;

        $configAdapter
            ->expects($this->any())
            ->method('getInstance')
            ->willReturn(null)
        ;

        $configAdapter
            ->expects($this->any())
            ->method('get')
            ->willReturnCallback(function ($key) {
                switch ($key) {
                    case 'characterSet':
                        return 'UTF-8';

                    case 'useAutoItem':
                        return true;

                    default:
                        return null;
                }
            })
        ;

        return $configAdapter;
    }
}
