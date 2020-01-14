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

use Contao\CoreBundle\PageType\PageTypeConfigInterface;
use Contao\CoreBundle\PageType\PageTypeInterface;
use Contao\CoreBundle\PageType\PageTypeRegistry;
use Contao\CoreBundle\PageType\UnknownPageTypeException;
use Contao\PageModel;
use Contao\TestCase\ContaoTestCase;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

class PageTypeRegistryTest extends ContaoTestCase
{
    public function testPageTypeRegistration(): void
    {
        $pageType = $this->mockPageType('test');
        $pageTypeRegistry = $this->createPageTypeRegistry();

        $this->assertFalse($pageTypeRegistry->has('test'));

        $pageTypeRegistry->register($pageType);;

        $this->assertTrue($pageTypeRegistry->has('test'));
        $this->assertSame($pageType, $pageTypeRegistry->get('test'));
        $this->assertFalse($pageTypeRegistry->has('example'));
    }

    public function testPageTypeCanBeOverridden(): void
    {
        $pageTypeA = $this->mockPageType('test');
        $pageTypeB = $this->mockPageType('test');

        $pageTypeRegistry = $this->createPageTypeRegistry([$pageTypeA]);

        $this->assertSame($pageTypeA, $pageTypeRegistry->get('test'));

        $pageTypeRegistry->register($pageTypeB);
        $this->assertSame($pageTypeB, $pageTypeRegistry->get('test'));
    }

    public function testThrowsUnknownPageTypeException(): void
    {
        $pageTypeRegistry = $this->createPageTypeRegistry();

        $this->expectException(UnknownPageTypeException::class);
        $pageTypeRegistry->get('test');
    }

    public function testIterator(): void
    {
        $pageType = $this->mockPageType('name');
        $pageTypeRegistry = $this->createPageTypeRegistry([$pageType]);

        $this->assertInstanceOf(\Traversable::class, $pageTypeRegistry);

        foreach ($pageTypeRegistry as $value) {
            $this->assertSame($pageType, $value);
        }
    }

    public function testCreatePageTypeConfig(): void
    {
        $pageTypeConfig = $this->createMock(PageTypeConfigInterface::class);
        $eventDispatcher = $this->createMock(EventDispatcherInterface::class);

        $pageType = $this->mockPageType('test');
        $pageType
            ->method('createPageTypeConfig')
            ->willReturn($pageTypeConfig);

        $pageTypeRegistry = $this->createPageTypeRegistry([$pageType], $eventDispatcher);

        $pageType
            ->expects($this->once())
            ->method('createPageTypeConfig');

        $page = $this->mockClassWithProperties(PageModel::class);
        $page->type = 'test';

        $eventDispatcher
            ->expects($this->once())
            ->method('dispatch');

        $pageTypeRegistry->createPageTypeConfig($page);
    }

    private function createPageTypeRegistry(array $pageTypes = [], ?EventDispatcherInterface $eventDispatcher = null): PageTypeRegistry
    {
        if (null === $eventDispatcher) {
            $eventDispatcher = $this->getMockForAbstractClass(EventDispatcherInterface::class);
        }

        $pageTypeRegistry = new PageTypeRegistry($eventDispatcher);

        foreach ($pageTypes as $pageType) {
            $pageTypeRegistry->register($pageType);
        }

        return $pageTypeRegistry;
    }

    protected function mockPageType(string $name): PageTypeInterface
    {
        $pageType = $this->createMock(PageTypeInterface::class);
        $pageType
            ->method('getName')
            ->willReturn($name);

        return $pageType;
    }

}
