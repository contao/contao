<?php

/*
 * This file is part of Contao.
 *
 * Copyright (c) 2005-2017 Leo Feyer
 *
 * @license LGPL-3.0+
 */

namespace Contao\NewsBundle\Tests\Menu;

use Contao\BackendUser;
use Contao\CoreBundle\Framework\Adapter;
use Contao\CoreBundle\Framework\ContaoFrameworkInterface;
use Contao\CoreBundle\Menu\PickerMenuProviderInterface;
use Contao\NewsArchiveModel;
use Contao\NewsBundle\Menu\NewsPickerProvider;
use Contao\NewsModel;
use Knp\Menu\MenuFactory;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;

/**
 * Tests the NewsPickerProvider class.
 *
 * @author Leo Feyer <https:/github.com/leofeyer>
 */
class NewsPickerProviderTest extends TestCase
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

        $this->provider = $this->mockPickerProvider();
    }

    /**
     * Tests the object instantiation.
     */
    public function testInstantiation()
    {
        $this->assertInstanceOf('Contao\NewsBundle\Menu\NewsPickerProvider', $this->provider);
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

        $eventPicker = $menu->getChild('newsPicker');

        $this->assertNotNull($eventPicker);
        $this->assertSame('news', $eventPicker->getLinkAttribute('class'));
        $this->assertSame('contao_backend:do=news', $eventPicker->getUri());
    }

    /**
     * Tests the supports() method.
     */
    public function testSupports()
    {
        $this->assertTrue($this->provider->supports('link'));
        $this->assertFalse($this->provider->supports('page'));
    }

    /**
     * Tests the supportsTable() method.
     */
    public function testSupportsTable()
    {
        $this->assertTrue($this->provider->supportsTable('tl_news'));
        $this->assertFalse($this->provider->supportsTable('tl_page'));
    }

    /**
     * Tests the processSelection() method.
     */
    public function testProcessSelection()
    {
        $this->assertSame('{{news_url::2}}', $this->provider->processSelection(2));
    }

    /**
     * Tests the canHandle() method.
     */
    public function testCanHandle()
    {
        $request = new Request();
        $request->query->set('value', '{{news_url::2}}');

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
        $request->query->set('value', '{{news_url::42}}');

        $this->assertSame(
            'contao_backend:value=42:do=news:table=tl_news:id=2',
            $this->provider->getPickerUrl($request)
        );

        $this->assertSame('contao_backend:value=42:do=news', $this->provider->getPickerUrl($request));
        $this->assertSame('contao_backend:value=42:do=news', $this->provider->getPickerUrl($request));
        $this->assertSame('contao_backend:value=42:do=news', $this->provider->getPickerUrl($request));
    }

    /**
     * Mocks a picker provider.
     *
     * @return PickerMenuProviderInterface
     */
    protected function mockPickerProvider()
    {
        $router = $this->createMock(RouterInterface::class);

        $router
            ->method('generate')
            ->willReturnCallback(function ($name, $params) {
                $url = $name;

                foreach ($params as $key => $value) {
                    $url .= ':'.$key.'='.$value;
                }

                return $url;
            })
        ;

        $user = $this->createMock(BackendUser::class);

        $user
            ->method('hasAccess')
            ->willReturn(true)
        ;

        $token = $this->createMock(TokenInterface::class);

        $token
            ->method('getUser')
            ->willReturn($user)
        ;

        $tokenStorage = $this->createMock(TokenStorageInterface::class);

        $tokenStorage
            ->method('getToken')
            ->willReturn($token)
        ;

        $request = new Request();

        $requestStack = new RequestStack();
        $requestStack->push($request);

        $archiveModel = $this->createMock(NewsArchiveModel::class);

        $archiveModel
            ->method('__get')
            ->willReturn(2)
        ;

        $newsModel = $this->createMock(NewsModel::class);

        $newsModel
            ->method('getRelated')
            ->willReturnOnConsecutiveCalls($archiveModel, null)
        ;

        $adapter = $this
            ->getMockBuilder(Adapter::class)
            ->disableOriginalConstructor()
            ->setMethods(['findById'])
            ->getMock()
        ;

        $adapter
            ->method('findById')
            ->willReturnOnConsecutiveCalls($newsModel, $newsModel, null)
        ;

        $framework = $this->createMock(ContaoFrameworkInterface::class);

        $framework
            ->method('getAdapter')
            ->willReturn($adapter)
        ;

        $provider = new NewsPickerProvider($router, $requestStack, $tokenStorage);
        $provider->setFramework($framework);

        return $provider;
    }
}
