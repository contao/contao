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

use Contao\CoreBundle\Picker\PagePickerProvider;
use Contao\CoreBundle\Picker\PickerConfig;
use Contao\TestCase\ContaoTestCase;
use Knp\Menu\FactoryInterface;
use Knp\Menu\ItemInterface;
use Knp\Menu\MenuItem;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Security\Core\Security;
use Symfony\Contracts\Translation\TranslatorInterface;

class PagePickerProviderTest extends ContaoTestCase
{
    public function testCreatesTheMenuItem(): void
    {
        $config = json_encode(
            [
                'context' => 'link',
                'extras' => [],
                'current' => 'pagePicker',
                'value' => '',
            ],
            JSON_THROW_ON_ERROR
        );

        if (\function_exists('gzencode') && false !== ($encoded = @gzencode($config))) {
            $config = $encoded;
        }

        $picker = $this->getPicker();
        $item = $picker->createMenuItem(new PickerConfig('link', [], '', 'pagePicker'));
        $uri = 'contao_backend?do=page&popup=1&picker='.strtr(base64_encode($config), '+/=', '-_,');

        $this->assertSame('Page picker', $item->getLabel());
        $this->assertSame(['class' => 'pagePicker'], $item->getLinkAttributes());
        $this->assertTrue($item->isCurrent());
        $this->assertSame($uri, $item->getUri());
    }

    public function testChecksIfAMenuItemIsCurrent(): void
    {
        $picker = $this->getPicker();

        $this->assertTrue($picker->isCurrent(new PickerConfig('page', [], '', 'pagePicker')));
        $this->assertFalse($picker->isCurrent(new PickerConfig('page', [], '', 'filePicker')));
        $this->assertTrue($picker->isCurrent(new PickerConfig('link', [], '', 'pagePicker')));
        $this->assertFalse($picker->isCurrent(new PickerConfig('link', [], '', 'filePicker')));
    }

    public function testReturnsTheCorrectName(): void
    {
        $picker = $this->getPicker();

        $this->assertSame('pagePicker', $picker->getName());
    }

    public function testChecksIfAContextIsSupported(): void
    {
        $picker = $this->getPicker(true);

        $this->assertTrue($picker->supportsContext('page'));
        $this->assertTrue($picker->supportsContext('link'));
        $this->assertFalse($picker->supportsContext('file'));
    }

    public function testChecksIfModuleAccessIsGranted(): void
    {
        $picker = $this->getPicker(false);

        $this->assertFalse($picker->supportsContext('link'));
    }

    public function testChecksIfAValueIsSupported(): void
    {
        $picker = $this->getPicker();

        $this->assertTrue($picker->supportsValue(new PickerConfig('page', [], 5)));
        $this->assertFalse($picker->supportsValue(new PickerConfig('page', [], '{{article_url::5}}')));
        $this->assertTrue($picker->supportsValue(new PickerConfig('link', [], '{{link_url::5}}')));
        $this->assertFalse($picker->supportsValue(new PickerConfig('link', [], '{{article_url::5}}')));
    }

    public function testReturnsTheDcaTable(): void
    {
        $picker = $this->getPicker();

        $this->assertSame('tl_page', $picker->getDcaTable());
    }

    public function testReturnsTheDcaAttributes(): void
    {
        $picker = $this->getPicker();

        $extra = [
            'fieldType' => 'checkbox',
            'rootNodes' => [1, 2, 3],
            'source' => 'tl_page.2',
        ];

        $this->assertSame(
            [
                'fieldType' => 'checkbox',
                'rootNodes' => [1, 2, 3],
                'value' => [5],
            ],
            $picker->getDcaAttributes(new PickerConfig('page', $extra, '5'))
        );

        $this->assertSame(
            [
                'fieldType' => 'radio',
                'value' => '5',
                'flags' => ['urlattr'],
            ],
            $picker->getDcaAttributes(new PickerConfig('link', [], '{{link_url::5|urlattr}}'))
        );

        $this->assertSame(
            ['fieldType' => 'radio'],
            $picker->getDcaAttributes(new PickerConfig('link', [], '{{article_url::5}}'))
        );
    }

    public function testConvertsTheDcaValue(): void
    {
        $picker = $this->getPicker();

        $this->assertSame(5, $picker->convertDcaValue(new PickerConfig('page'), 5));
        $this->assertSame('{{link_url::5}}', $picker->convertDcaValue(new PickerConfig('link'), 5));
    }

    public function testConvertsTheDcaValueWithACustomInsertTag(): void
    {
        $picker = $this->getPicker();

        // General insertTag extra
        $this->assertSame(
            5,
            $picker->convertDcaValue(
                new PickerConfig('page', ['insertTag' => '{{link_url::%s|absolute}}']),
                5
            )
        );

        $this->assertSame(
            '{{link_url::5|absolute}}',
            $picker->convertDcaValue(
                new PickerConfig('link', ['insertTag' => '{{link_url::%s|absolute}}']),
                5
            )
        );

        // Picker specific insertTag extra
        $this->assertSame(
            5,
            $picker->convertDcaValue(
                new PickerConfig('page', ['pagePicker' => ['insertTag' => '{{specific_insert_tag::%s}}']]),
                5
            )
        );

        $this->assertSame(
            '{{specific_insert_tag::5}}',
            $picker->convertDcaValue(
                new PickerConfig('link', ['pagePicker' => ['insertTag' => '{{specific_insert_tag::%s}}']]),
                5
            )
        );
    }

    private function getPicker(bool|null $accessGranted = null): PagePickerProvider
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
                }
            )
        ;

        $router = $this->createMock(RouterInterface::class);
        $router
            ->method('generate')
            ->willReturnCallback(static fn (string $name, array $params): string => $name.'?'.http_build_query($params))
        ;

        $translator = $this->createMock(TranslatorInterface::class);
        $translator
            ->method('trans')
            ->willReturn('Page picker')
        ;

        return new PagePickerProvider($menuFactory, $router, $translator, $security);
    }
}
