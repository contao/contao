<?php

/*
 * This file is part of Contao.
 *
 * Copyright (c) 2005-2017 Leo Feyer
 *
 * @license LGPL-3.0+
 */

namespace Contao\CoreBundle\Tests\Picker;

use Contao\BackendUser;
use Contao\CoreBundle\Framework\Adapter;
use Contao\CoreBundle\Framework\ContaoFramework;
use Contao\CoreBundle\Picker\FilePickerProvider;
use Contao\CoreBundle\Picker\PickerConfig;
use Contao\FilesModel;
use Contao\StringUtil;
use Knp\Menu\FactoryInterface;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;

/**
 * Tests the FilePickerProvider class.
 *
 * @author Leo Feyer <https://github.com/leofeyer>
 */
class FilePickerProviderTest extends TestCase
{
    /**
     * @var FilePickerProvider
     */
    protected $provider;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        parent::setUp();

        $menuFactory = $this->createMock(FactoryInterface::class);

        $menuFactory
            ->method('createItem')
            ->willReturnArgument(1)
        ;

        $router = $this->createMock(RouterInterface::class);

        $router
            ->method('generate')
            ->willReturnCallback(
                function ($name, array $params) {
                    return $name.'?'.http_build_query($params);
                }
            )
        ;

        $filesModel = $this->createMock(FilesModel::class);

        $filesModel
            ->method('__get')
            ->willReturnCallback(function ($key) {
                switch ($key) {
                    case 'path':
                        return '/foobar';

                    case 'uuid':
                        return StringUtil::uuidToBin('82243f46-a4c3-11e3-8e29-000c29e44aea');

                    default:
                        return null;
                }
            })
        ;

        $filesAdapter = $this
            ->getMockBuilder(Adapter::class)
            ->disableOriginalConstructor()
            ->setMethods(['findByUuid', 'findByPath'])
            ->getMock()
        ;

        $filesAdapter
            ->method('findByUuid')
            ->willReturn($filesModel)
        ;

        $filesAdapter
            ->method('findByPath')
            ->willReturnOnConsecutiveCalls($filesModel, null)
        ;

        $framwork = $this->createMock(ContaoFramework::class);

        $framwork
            ->method('getAdapter')
            ->willReturn($filesAdapter)
        ;

        $this->provider = new FilePickerProvider($menuFactory, $router, __DIR__);
        $this->provider->setFramework($framwork);

        $GLOBALS['TL_LANG']['MSC']['filePicker'] = 'File picker';
    }

    /**
     * {@inheritdoc}
     */
    protected function tearDown()
    {
        parent::tearDown();

        unset($GLOBALS['TL_LANG']);
    }

    /**
     * Tests the object instantiation.
     */
    public function testCanBeInstantiated()
    {
        $this->assertInstanceOf('Contao\CoreBundle\Picker\FilePickerProvider', $this->provider);
    }

    /**
     * Tests the createMenuItem() method.
     */
    public function testCreatesTheMenuItem()
    {
        $picker = json_encode([
            'context' => 'link',
            'extras' => [],
            'current' => 'filePicker',
            'value' => '',
        ]);

        if (\function_exists('gzencode') && false !== ($encoded = @gzencode($picker))) {
            $picker = $encoded;
        }

        $this->assertSame(
            [
                'label' => 'File picker',
                'linkAttributes' => ['class' => 'filePicker'],
                'current' => true,
                'uri' => 'contao_backend?do=files&popup=1&picker='.strtr(base64_encode($picker), '+/=', '-_,'),
            ], $this->provider->createMenuItem(new PickerConfig('link', [], '', 'filePicker'))
        );
    }

    /**
     * Tests the isCurrent() method.
     */
    public function testChecksIfAMenuItemIsCurrent()
    {
        $this->assertTrue($this->provider->isCurrent(new PickerConfig('file', [], '', 'filePicker')));
        $this->assertFalse($this->provider->isCurrent(new PickerConfig('file', [], '', 'pagePicker')));

        $this->assertTrue($this->provider->isCurrent(new PickerConfig('link', [], '', 'filePicker')));
        $this->assertFalse($this->provider->isCurrent(new PickerConfig('link', [], '', 'pagePicker')));
    }

    /**
     * Tests the getName() method.
     */
    public function testReturnsTheCorrectName()
    {
        $this->assertSame('filePicker', $this->provider->getName());
    }

    /**
     * Tests the supportsContext() method.
     */
    public function testChecksIfAContextIsSupported()
    {
        $user = $this
            ->getMockBuilder(BackendUser::class)
            ->disableOriginalConstructor()
            ->setMethods(['hasAccess'])
            ->getMock()
        ;

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

        $this->provider->setTokenStorage($tokenStorage);

        $this->assertTrue($this->provider->supportsContext('file'));
        $this->assertTrue($this->provider->supportsContext('link'));
        $this->assertFalse($this->provider->supportsContext('page'));
    }

    /**
     * Tests the supportsContext() method without token storage.
     */
    public function testFailsToCheckTheContextIfThereIsNoTokenStorage()
    {
        $this->expectException('RuntimeException');
        $this->expectExceptionMessage('No token storage provided');

        $this->provider->supportsContext('link');
    }

    /**
     * Tests the supportsContext() method without token.
     */
    public function testFailsToCheckTheContextIfThereIsNoToken()
    {
        $tokenStorage = $this->createMock(TokenStorageInterface::class);

        $tokenStorage
            ->method('getToken')
            ->willReturn(null)
        ;

        $this->provider->setTokenStorage($tokenStorage);

        $this->expectException('RuntimeException');
        $this->expectExceptionMessage('No token provided');

        $this->provider->supportsContext('link');
    }

    /**
     * Tests the supportsContext() method without a user object.
     */
    public function testFailsToCheckTheContextIfThereIsNoUser()
    {
        $token = $this->createMock(TokenInterface::class);

        $token
            ->method('getUser')
            ->willReturn(null)
        ;

        $tokenStorage = $this->createMock(TokenStorageInterface::class);

        $tokenStorage
            ->method('getToken')
            ->willReturn($token)
        ;

        $this->provider->setTokenStorage($tokenStorage);

        $this->expectException('RuntimeException');
        $this->expectExceptionMessage('The token does not contain a back end user object');

        $this->provider->supportsContext('link');
    }

    /**
     * Tests the supportsValue() method.
     */
    public function testChecksIfAValueIsSupported()
    {
        $uuid = '82243f46-a4c3-11e3-8e29-000c29e44aea';

        $this->assertTrue($this->provider->supportsValue(new PickerConfig('file', [], $uuid)));
        $this->assertFalse($this->provider->supportsValue(new PickerConfig('file', [], '/home/foobar.txt')));

        $this->assertTrue($this->provider->supportsValue(new PickerConfig('link', [], '{{file::'.$uuid.'}}')));
        $this->assertFalse($this->provider->supportsValue(new PickerConfig('link', [], '/home/foobar.txt')));
    }

    /**
     * Tests the getDcaTable() method.
     */
    public function testReturnsTheDcaTable()
    {
        $this->assertSame('tl_files', $this->provider->getDcaTable());
    }

    /**
     * Tests the getDcaAttributes() method.
     */
    public function testReturnsTheDcaAttributes()
    {
        $extra = [
            'fieldType' => 'checkbox',
            'files' => true,
        ];

        $this->assertSame(
            [
                'fieldType' => 'checkbox',
                'files' => true,
                'value' => ['/foobar'],
            ],
            $this->provider->getDcaAttributes(new PickerConfig('file', $extra, '82243f46-a4c3-11e3-8e29-000c29e44aea'))
        );

        $this->assertSame(
            [
                'fieldType' => 'radio',
                'filesOnly' => true,
                'value' => '/foobar',
            ],
            $this->provider->getDcaAttributes(
                new PickerConfig('link', $extra, '{{file::82243f46-a4c3-11e3-8e29-000c29e44aea}}')
            )
        );

        $this->assertSame(
            [
                'fieldType' => 'radio',
                'filesOnly' => true,
                'value' => 'foo/bar.jpg',
            ],
            $this->provider->getDcaAttributes(new PickerConfig('link', $extra, 'foo/bar.jpg'))
        );

        $this->assertSame(
            [
                'fieldType' => 'radio',
                'filesOnly' => true,
                'value' => '/foobar',
            ],
            $this->provider->getDcaAttributes(new PickerConfig('link', [], '/foobar'))
        );

        $this->assertSame(
            [
                'fieldType' => 'radio',
                'filesOnly' => true,
                'value' => 'foo/b%C3%A4r%20baz.jpg',
            ],
            $this->provider->getDcaAttributes(new PickerConfig('link', [], 'foo/bär baz.jpg'))
        );
    }

    /**
     * Tests the convertDcaValue() method.
     */
    public function testConvertsTheDcaValue()
    {
        $this->assertSame(
            '/foobar',
            $this->provider->convertDcaValue(new PickerConfig('file'), '/foobar')
        );

        $this->assertSame(
            '{{file::82243f46-a4c3-11e3-8e29-000c29e44aea}}',
            $this->provider->convertDcaValue(new PickerConfig('link'), '/foobar')
        );

        $this->assertSame(
            '/foobar',
            $this->provider->convertDcaValue(new PickerConfig('link'), '/foobar')
        );
    }
}
