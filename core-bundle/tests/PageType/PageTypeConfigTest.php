<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Tests\PageType;

use Contao\CoreBundle\PageType\PageTypeConfig;
use Contao\CoreBundle\PageType\PageTypeConfigInterface;
use Contao\CoreBundle\PageType\PageTypeInterface;
use Contao\PageModel;
use Contao\TestCase\ContaoTestCase;
use PHPUnit\Framework\MockObject\MockObject;

class PageTypeConfigTest extends ContaoTestCase
{
    public function testImplementsPageTypeConfigInterface(): void
    {
        $pageTypeConfig = $this->createPageTypeConfig();

        $this->assertInstanceOf(PageTypeConfigInterface::class, $pageTypeConfig);
    }

    public function testContainsPageType(): void
    {
        $pageType = $this->createMock(PageTypeInterface::class);
        $pageTypeConfig = $this->createPageTypeConfig(null, $pageType);

        $this->assertSame($pageType, $pageTypeConfig->getPageType());
    }

    public function testContainsPageModel(): void
    {
        $pageTypeConfig = $this->createPageTypeConfig(['id' => 5]);

        $this->assertInstanceOf(PageModel::class, $pageTypeConfig->getPageModel());
        $this->assertSame(5, $pageTypeConfig->getPageModel()->id);
    }

    public function testGetOptions(): void
    {
        $options = ['foo' => 'bar'];
        $pageTypeConfig = $this->createPageTypeConfig(null, null, $options);

        $this->assertSame($options, $pageTypeConfig->getOptions());
    }

    public function testSetOptionAddOption(): void
    {
        $options = ['foo' => 'bar'];
        $pageTypeConfig = $this->createPageTypeConfig(null, null, $options);

        $this->assertSame($options, $pageTypeConfig->getOptions());
        $this->assertFalse($pageTypeConfig->hasOption('bar'));

        $pageTypeConfig->setOption('bar', 'baz');

        $this->assertSame(['foo' => 'bar', 'bar' => 'baz'], $pageTypeConfig->getOptions());
        $this->assertTrue($pageTypeConfig->hasOption('bar'));
    }

    public function testSetOptionsMergeOptions(): void
    {
        $options = ['foo' => 'bar'];
        $pageTypeConfig = $this->createPageTypeConfig(null, null, $options);

        $this->assertSame($options, $pageTypeConfig->getOptions());
        $this->assertFalse($pageTypeConfig->hasOption('bar'));


        $pageTypeConfig->setOptions(['bar' => 'baz']);

        $this->assertSame(['foo' => 'bar', 'bar' => 'baz'], $pageTypeConfig->getOptions());
        $this->assertTrue($pageTypeConfig->hasOption('bar'));
    }

    public function testGetOption(): void
    {
        $options = ['foo' => 'bar'];
        $pageTypeConfig = $this->createPageTypeConfig(null, null, $options);

        $this->assertSame('bar', $pageTypeConfig->getOption('foo'));
    }

    public function testGetDefaultValueIfOptionNotExists(): void
    {
        $pageTypeConfig = $this->createPageTypeConfig();

        $this->assertFalse($pageTypeConfig->hasOption('bar'));
        $this->assertNull($pageTypeConfig->getOption('bar'));
        $this->assertSame('foo', $pageTypeConfig->getOption('bar', 'foo'));
    }

    private function createPageTypeConfig(?array $pageModelProperties = null, ?PageTypeInterface $pageType = null, array $options = []) : PageTypeConfigInterface
    {
        $pageModel = $this->createPageModel($pageModelProperties);

        if (null === $pageType) {
            $pageType = $this->createMock(PageTypeInterface::class);
        }

        return new PageTypeConfig($pageType, $pageModel, $options);
    }


    /**
     * @return MockObject|PageModel
     */
    private function createPageModel(?array $pageModelProperties = null): PageModel
    {
        if (null === $pageModelProperties) {
            $pageModelProperties = [
                'id' => 12,
                'type' => 'regular'
            ];
        }

        $pageModel = $this->mockClassWithProperties(PageModel::class);
        foreach ($pageModelProperties as $name => $value) {
            $pageModel->{$name} = $value;
        }
        return $pageModel;
    }
}
