<?php

/*
 * This file is part of Contao.
 *
 * Copyright (c) 2005-2017 Leo Feyer
 *
 * @license LGPL-3.0+
 */

namespace Contao\CoreBundle\Tests\Menu;

use Contao\CoreBundle\Framework\Adapter;
use Contao\CoreBundle\Framework\ContaoFrameworkInterface;
use Contao\CoreBundle\Menu\FilePickerProvider;
use Contao\CoreBundle\Tests\TestCase;
use Contao\FilesModel;
use Contao\StringUtil;
use Knp\Menu\MenuFactory;
use Symfony\Component\HttpFoundation\Request;

/**
 * Tests the FilePickerProvider class.
 *
 * @author Leo Feyer <https:/github.com/leofeyer>
 */
class FilePickerProviderTest extends TestCase
{
    /**
     * @var FilePickerProvider
     */
    private $provider;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        parent::setUp();

        $model = $this->createMock(FilesModel::class);

        $model
            ->method('__get')
            ->willReturnCallback(function ($key) {
                switch ($key) {
                    case 'uuid':
                        return StringUtil::uuidToBin('632cce39-cea3-11e6-87f4-ac87a32709d5');

                    case 'path':
                        return 'files/foo.jpg';

                    default:
                        return null;
                }
            })
        ;

        $adapter = $this
            ->getMockBuilder(Adapter::class)
            ->disableOriginalConstructor()
            ->setMethods(['findByPath', 'findByUuid'])
            ->getMock()
        ;

        $adapter
            ->method('findByPath')
            ->willReturn($model)
        ;

        $adapter
            ->method('findByUuid')
            ->willReturn($model)
        ;

        $framework = $this->createMock(ContaoFrameworkInterface::class);

        $framework
            ->method('getAdapter')
            ->willReturn($adapter)
        ;

        $this->provider = $this->mockPickerProvider(FilePickerProvider::class);
        $this->provider->setFramework($framework);
    }

    /**
     * Tests the object instantiation.
     */
    public function testInstantiation()
    {
        $this->assertInstanceOf('Contao\CoreBundle\Menu\FilePickerProvider', $this->provider);
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

        $filePicker = $menu->getChild('filePicker');

        $this->assertNotNull($filePicker);
        $this->assertSame('filemounts', $filePicker->getLinkAttribute('class'));
        $this->assertSame('contao_backend:do=files', $filePicker->getUri());
    }

    /**
     * Tests the supports() method.
     */
    public function testSupports()
    {
        $this->assertFalse($this->provider->supports('page'));
        $this->assertTrue($this->provider->supports('file'));
        $this->assertTrue($this->provider->supports('link'));
    }

    /**
     * Tests the supportsTable() method.
     */
    public function testSupportsTable()
    {
        $this->assertTrue($this->provider->supportsTable('tl_files'));
        $this->assertFalse($this->provider->supportsTable('tl_page'));
    }

    /**
     * Tests the processSelection() method.
     */
    public function testProcessSelection()
    {
        $this->assertSame(
            json_encode([
                'content' => 'files/foo.jpg',
                'tag' => '{{file::632cce39-cea3-11e6-87f4-ac87a32709d5}}',
            ]),
            $this->provider->processSelection('files/foo.jpg')
        );
    }

    /**
     * Tests the canHandle() method.
     */
    public function testCanHandle()
    {
        $request = new Request();
        $request->query->set('value', 'files/foo.jpg');

        $this->assertTrue($this->provider->canHandle($request));

        $request->query->set('value', '{{file::632cce39-cea3-11e6-87f4-ac87a32709d5}}');

        $this->assertTrue($this->provider->canHandle($request));

        $request->query->set('value', '{{link_url::2}}');

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
        $request->query->set('value', 'files/foo.jpg');

        $this->assertSame('contao_backend:value=files/foo.jpg:do=files', $this->provider->getPickerUrl($request));

        $request->query->set('value', '{{file::632cce39-cea3-11e6-87f4-ac87a32709d5}}');

        $this->assertSame('contao_backend:value=files/foo.jpg:do=files', $this->provider->getPickerUrl($request));
    }
}
