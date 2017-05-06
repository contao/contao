<?php

/*
 * This file is part of Contao.
 *
 * Copyright (c) 2005-2017 Leo Feyer
 *
 * @license LGPL-3.0+
 */

namespace Contao\CoreBundle\Tests\Menu;

use Contao\CoreBundle\Menu\FilePickerProvider;
use Contao\CoreBundle\Menu\PagePickerProvider;
use Contao\CoreBundle\Menu\PickerMenuBuilder;
use Contao\CoreBundle\Menu\PickerMenuBuilderInterface;
use Contao\CoreBundle\Tests\TestCase;
use Knp\Menu\Matcher\Matcher;
use Knp\Menu\MenuFactory;
use Knp\Menu\Renderer\ListRenderer;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\RouterInterface;

/**
 * Tests the PickerMenuBuilder class.
 *
 * @author Leo Feyer <https:/github.com/leofeyer>
 */
class PickerMenuBuilderTest extends TestCase
{
    /**
     * @var PickerMenuBuilderInterface
     */
    private $menuBuilder;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        parent::setUp();

        $factory = new MenuFactory();
        $renderer = new ListRenderer(new Matcher());
        $router = $this->getMock(RouterInterface::class);

        $router
            ->expects($this->any())
            ->method('generate')
            ->willReturnCallback(function ($name, $params) {
                $url = $name;

                foreach ($params as $key => $value) {
                    $url .= ':'.$key.'='.$value;
                }

                return $url;
            })
        ;

        $menuBuilder = new PickerMenuBuilder($factory, $renderer, $router);
        $menuBuilder->addProvider($this->mockPickerProvider(PagePickerProvider::class));
        $menuBuilder->addProvider($this->mockPickerProvider(FilePickerProvider::class));

        $this->menuBuilder = $menuBuilder;
    }

    /**
     * Tests the object instantiation.
     */
    public function testInstantiation()
    {
        $this->assertInstanceOf('Contao\CoreBundle\Menu\PickerMenuBuilder', $this->menuBuilder);
    }

    /**
     * Tests the createMenu() method.
     */
    public function testCreateMenu()
    {
        $GLOBALS['TL_LANG']['MSC']['pagePicker'] = 'Pages';

        $menu = <<<'EOF'
<ul>
  <li class="first">
    <a href="contao_backend:do=page" class="pagemounts">Pages</a>
  </li>
  <li class="last">
    <a href="contao_backend:do=files" class="filemounts">filePicker</a>
  </li>
</ul>

EOF;

        $this->assertSame($menu, $this->menuBuilder->createMenu('link'));
        $this->assertSame('', $this->menuBuilder->createMenu('page'));
    }

    /**
     * Tests the supportsTable() method.
     */
    public function testSupportsTable()
    {
        $this->assertTrue($this->menuBuilder->supportsTable('tl_page'));
        $this->assertTrue($this->menuBuilder->supportsTable('tl_files'));
        $this->assertFalse($this->menuBuilder->supportsTable('tl_member'));
    }

    /**
     * Tests the processSelection() method.
     */
    public function testProcessSelection()
    {
        $this->assertSame('foo', $this->menuBuilder->processSelection('tl_files', 'foo'));
        $this->assertSame('{{link_url::2}}', $this->menuBuilder->processSelection('tl_page', 2));
        $this->assertSame('bar', $this->menuBuilder->processSelection('tl_member', 'bar'));
    }

    /**
     * Tests the getPickerUrl() method.
     */
    public function testGetPickerUrl()
    {
        $request = new Request();
        $request->query->set('value', '{{link_url::42}}');

        $this->assertSame('contao_backend:value=42:do=page', $this->menuBuilder->getPickerUrl($request));

        $request = new Request();
        $request->query->set('value', '{{news_url::42}}');

        $this->assertSame(
            'contao_backend:do=page:value={{news_url::42}}',
            $this->menuBuilder->getPickerUrl($request)
        );

        $this->assertSame('contao_backend:do=page', $this->menuBuilder->getPickerUrl(new Request()));
    }
}
