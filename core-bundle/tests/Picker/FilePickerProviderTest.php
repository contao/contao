<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Tests\Picker;

use Contao\CoreBundle\Picker\FilePickerProvider;
use Contao\CoreBundle\Picker\PickerConfig;
use Contao\CoreBundle\Tests\TestCase;
use Contao\FilesModel;
use Contao\StringUtil;
use Knp\Menu\FactoryInterface;
use Knp\Menu\ItemInterface;
use Knp\Menu\MenuItem;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Security\Core\Security;
use Symfony\Contracts\Translation\TranslatorInterface;

class FilePickerProviderTest extends TestCase
{
    public function testCreatesTheMenuItem(): void
    {
        $config = json_encode(
            [
                'context' => 'link',
                'extras' => [],
                'current' => 'filePicker',
                'value' => '',
            ],
            JSON_THROW_ON_ERROR
        );

        if (\function_exists('gzencode') && false !== ($encoded = @gzencode($config))) {
            $config = $encoded;
        }

        $picker = $this->getPicker();
        $item = $picker->createMenuItem(new PickerConfig('link', [], '', 'filePicker'));
        $uri = 'contao_backend?do=files&popup=1&picker='.strtr(base64_encode($config), '+/=', '-_,');

        $this->assertSame('File picker', $item->getLabel());
        $this->assertSame(['class' => 'filePicker'], $item->getLinkAttributes());
        $this->assertTrue($item->isCurrent());
        $this->assertSame($uri, $item->getUri());
    }

    public function testChecksIfAMenuItemIsCurrent(): void
    {
        $picker = $this->getPicker();

        $this->assertTrue($picker->isCurrent(new PickerConfig('file', [], '', 'filePicker')));
        $this->assertFalse($picker->isCurrent(new PickerConfig('file', [], '', 'pagePicker')));
        $this->assertTrue($picker->isCurrent(new PickerConfig('link', [], '', 'filePicker')));
        $this->assertFalse($picker->isCurrent(new PickerConfig('link', [], '', 'pagePicker')));
    }

    public function testReturnsTheCorrectName(): void
    {
        $picker = $this->getPicker();

        $this->assertSame('filePicker', $picker->getName());
    }

    public function testChecksIfAContextIsSupported(): void
    {
        $picker = $this->getPicker(true);

        $this->assertTrue($picker->supportsContext('file'));
        $this->assertTrue($picker->supportsContext('link'));
        $this->assertFalse($picker->supportsContext('page'));
    }

    public function testChecksIfModuleAccessIsGranted(): void
    {
        $picker = $this->getPicker(false);

        $this->assertFalse($picker->supportsContext('link'));
    }

    public function testChecksIfAValueIsSupported(): void
    {
        $picker = $this->getPicker();
        $uuid = '82243f46-a4c3-11e3-8e29-000c29e44aea';

        $this->assertTrue($picker->supportsValue(new PickerConfig('file', [], $uuid)));
        $this->assertFalse($picker->supportsValue(new PickerConfig('file', [], '/home/foobar.txt')));
        $this->assertTrue($picker->supportsValue(new PickerConfig('link', [], '{{file::'.$uuid.'}}')));
        $this->assertFalse($picker->supportsValue(new PickerConfig('link', [], '/home/foobar.txt')));
    }

    public function testReturnsTheDcaTable(): void
    {
        $picker = $this->getPicker();

        $this->assertSame('tl_files', $picker->getDcaTable());
    }

    public function testReturnsTheDcaAttributes(): void
    {
        $picker = $this->getPicker();

        $extra = [
            'fieldType' => 'checkbox',
            'files' => true,
        ];

        $uuid = '82243f46-a4c3-11e3-8e29-000c29e44aea';

        $this->assertSame(
            [
                'fieldType' => 'checkbox',
                'files' => true,
                'value' => ['/foobar'],
            ],
            $picker->getDcaAttributes(new PickerConfig('file', $extra, $uuid)),
        );

        $this->assertSame(
            [
                'files' => true,
                'fieldType' => 'radio',
                'value' => ['/foobar'],
            ],
            $picker->getDcaAttributes(new PickerConfig('file', ['files' => true], $uuid)),
        );

        $this->assertSame(
            [
                'fieldType' => 'radio',
                'filesOnly' => true,
                'value' => '/foobar',
                'flags' => ['urlattr'],
            ],
            $picker->getDcaAttributes(new PickerConfig('link', $extra, '{{file::'.$uuid.'|urlattr}}')),
        );

        $this->assertSame(
            [
                'fieldType' => 'radio',
                'filesOnly' => true,
                'value' => 'foo/bar.jpg',
            ],
            $picker->getDcaAttributes(new PickerConfig('link', $extra, 'foo/bar.jpg')),
        );

        $this->assertSame(
            [
                'fieldType' => 'radio',
                'filesOnly' => true,
                'value' => '/foobar',
            ],
            $picker->getDcaAttributes(new PickerConfig('link', [], '/foobar')),
        );

        $this->assertSame(
            [
                'fieldType' => 'radio',
                'filesOnly' => true,
                'value' => str_replace('%2F', '/', rawurlencode('foo/bär baz.jpg')),
            ],
            $picker->getDcaAttributes(new PickerConfig('link', [], 'foo/bär baz.jpg')),
        );

        $this->assertSame(
            [
                'fieldType' => 'radio',
                'filesOnly' => true,
                'value' => str_replace('%2F', '/', rawurlencode(__DIR__.'/foobar.jpg')),
            ],
            $picker->getDcaAttributes(new PickerConfig('link', [], __DIR__.'/foobar.jpg')),
        );
    }

    public function testConvertsTheDcaValue(): void
    {
        $picker = $this->getPicker();

        $this->assertSame(
            '/foobar',
            $picker->convertDcaValue(new PickerConfig('file'), '/foobar'),
        );

        $this->assertSame(
            '{{file::82243f46-a4c3-11e3-8e29-000c29e44aea}}',
            $picker->convertDcaValue(new PickerConfig('link'), '/foobar'),
        );

        $this->assertSame(
            '/foobar',
            $picker->convertDcaValue(new PickerConfig('link'), '/foobar'),
        );
    }

    public function testConvertsTheDcaValueWithACustomInsertTag(): void
    {
        $picker = $this->getPicker();

        $this->assertSame(
            '/foobar',
            $picker->convertDcaValue(new PickerConfig('file', ['insertTag' => '{{file_name::%s}}']), '/foobar'),
        );

        $this->assertSame(
            '{{file_name::82243f46-a4c3-11e3-8e29-000c29e44aea}}',
            $picker->convertDcaValue(new PickerConfig('link', ['insertTag' => '{{file_name::%s}}']), '/foobar'),
        );

        $this->assertSame(
            '/foobar',
            $picker->convertDcaValue(new PickerConfig('link', ['insertTag' => '{{file_name::%s}}']), '/foobar'),
        );
    }

    private function getPicker(bool|null $accessGranted = null): FilePickerProvider
    {
        $security = $this->createMock(Security::class);
        $security
            ->expects(null === $accessGranted ? $this->never() : $this->atLeastOnce())
            ->method('isGranted')
            ->willReturn($accessGranted ?? false)
        ;

        $menuFactory = $this->createMock(FactoryInterface::class);
        $menuFactory
            ->method('createItem')
            ->willReturnCallback(
                static function (string $name, array $data) use ($menuFactory): ItemInterface {
                    $item = new MenuItem($name, $menuFactory);
                    $item->setLabel($data['label']);
                    $item->setLinkAttributes($data['linkAttributes']);
                    $item->setCurrent($data['current']);
                    $item->setUri($data['uri']);

                    return $item;
                },
            )
        ;

        $router = $this->createMock(RouterInterface::class);
        $router
            ->method('generate')
            ->willReturnCallback(static fn (string $name, array $params): string => $name.'?'.http_build_query($params))
        ;

        $filesModel = $this->mockClassWithProperties(FilesModel::class);
        $filesModel->path = '/foobar';
        $filesModel->uuid = StringUtil::uuidToBin('82243f46-a4c3-11e3-8e29-000c29e44aea');

        $adapter = $this->mockAdapter(['findByUuid', 'findByPath']);
        $adapter
            ->method('findByUuid')
            ->willReturn($filesModel)
        ;

        $adapter
            ->method('findByPath')
            ->willReturnOnConsecutiveCalls($filesModel, null)
        ;

        $framwork = $this->mockContaoFramework([FilesModel::class => $adapter]);

        $translator = $this->createMock(TranslatorInterface::class);
        $translator
            ->method('trans')
            ->willReturn('File picker')
        ;

        $picker = new FilePickerProvider($menuFactory, $router, $translator, $security, __DIR__);
        $picker->setFramework($framwork);

        return $picker;
    }
}
