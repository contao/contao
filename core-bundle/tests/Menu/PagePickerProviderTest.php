<?php

/*
 * This file is part of Contao.
 *
 * Copyright (c) 2005-2017 Leo Feyer
 *
 * @license LGPL-3.0+
 */

namespace Contao\CoreBundle\Tests\Menu;

use Contao\CoreBundle\Menu\PagePickerProvider;
use Contao\CoreBundle\Menu\PickerMenuProviderInterface;
use Contao\CoreBundle\Tests\TestCase;
use Knp\Menu\MenuFactory;
use Symfony\Component\HttpFoundation\Request;

/**
 * Tests the PagePickerProvider class.
 *
 * @author Leo Feyer <https:/github.com/leofeyer>
 */
class PagePickerProviderTest extends TestCase
{
    /**
     * @var PickerMenuProviderInterface
     */
    private $provider;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        parent::setUp();

        $this->provider = $this->mockPickerProvider(PagePickerProvider::class);
    }

    /**
     * Tests the object instantiation.
     */
    public function testInstantiation()
    {
        $this->assertInstanceOf('Contao\CoreBundle\Menu\PagePickerProvider', $this->provider);
    }

    /**
     * Tests the createMenu() method.
     */
    public function testCreateMenu()
    {
        $factory = new MenuFactory();
        $menu = $factory->createItem('foo');

        $this->provider->createMenu($menu, $factory);

        $this->assertTrue($menu->hasChildren());

        $pagePicker = $menu->getChild('pagePicker');

        $this->assertNotNull($pagePicker);
        $this->assertEquals('pagemounts', $pagePicker->getLinkAttribute('class'));
        $this->assertEquals('contao_backend:do=page', $pagePicker->getUri());
    }

    /**
     * Tests the supports() method.
     */
    public function testSupports()
    {
        $this->assertTrue($this->provider->supports('page'));
        $this->assertFalse($this->provider->supports('file'));
        $this->assertTrue($this->provider->supports('link'));
    }

    /**
     * Tests the supportsTable() method.
     */
    public function testSupportsTable()
    {
        $this->assertTrue($this->provider->supportsTable('tl_page'));
        $this->assertFalse($this->provider->supportsTable('tl_files'));
    }

    /**
     * Tests the processSelection() method.
     */
    public function testProcessSelection()
    {
        $this->assertEquals('{{link_url::2}}', $this->provider->processSelection(2));
    }

    /**
     * Tests the canHandle() method.
     */
    public function testCanHandle()
    {
        $request = new Request();
        $request->query->set('value', '{{link_url::2}}');

        $this->assertTrue($this->provider->canHandle($request));

        $request->query->set('value', 'files/test/foo.jpg');

        $this->assertFalse($this->provider->canHandle($request));

        $request->query->remove('value');

        $this->assertFalse($this->provider->canHandle($request));
    }

    /**
     * Tests the getPickerUrl() method.
     */
    public function testGetPickerUrl()
    {
        $request = new Request();
        $request->query->set('value', '{{link_url::42}}');

        $this->assertEquals('contao_backend:value=42:do=page', $this->provider->getPickerUrl($request));
    }
}
